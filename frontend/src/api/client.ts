/** The JSON envelope returned by every plugin endpoint. */
export interface ApiEnvelope<T = unknown> {
  readonly ok: boolean
  readonly message: string
  readonly data: T
  readonly redirect?: string
}

/** Calls to the plugin endpoints under `ajax/`. */
export interface Api {
  get<T = unknown>(endpoint: string): Promise<ApiEnvelope<T>>
  post<T = unknown>(endpoint: string, body: unknown): Promise<ApiEnvelope<T>>
}

/** A request that failed before a plugin envelope could be read (network, GLPI error page). */
export class ApiError extends Error {
  /**
   * @param message What went wrong.
   * @param status The HTTP status, or 0 when the request never completed.
   */
  public constructor(
    message: string,
    public readonly status: number
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

/** Fetch implementation, injectable for tests. */
export type FetchLike = (input: string, init: RequestInit) => Promise<Response>

/**
 * Reads the CSRF token GLPI 11 publishes in a meta tag. GLPI 12 protects requests without
 * tokens (`Sec-Fetch-Site`), so the tag is absent there and no header is sent.
 * @param doc The page document.
 * @returns The token, or null when the page has none.
 */
export function csrfToken(doc: Document): string | null {
  const meta = doc.querySelector('meta[property="glpi:csrf_token"]')
  return meta?.getAttribute('content') ?? null
}

/**
 * Creates the client for the plugin endpoints. Every request carries
 * `X-Requested-With`, which the server requires and which makes GLPI 11 read the CSRF
 * token from the header.
 * @param doc The page document, used to find the CSRF token.
 * @param baseUrl Absolute URL of the plugin `ajax/` directory, ending with a slash.
 * @param fetcher The fetch function; defaults to the global one.
 * @returns The client.
 */
export function createApi(
  doc: Document,
  baseUrl: string,
  fetcher: FetchLike = (input, init) => fetch(input, init)
): Api {
  const send = async <T>(
    method: 'GET' | 'POST',
    endpoint: string,
    body?: unknown
  ): Promise<ApiEnvelope<T>> => {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
    const token = csrfToken(doc)
    if (token !== null) {
      headers['X-Glpi-Csrf-Token'] = token
    }
    const init: RequestInit = {
      method,
      headers,
      credentials: 'same-origin',
      cache: 'no-store'
    }
    if (body !== undefined) {
      headers['Content-Type'] = 'application/json'
      init.body = JSON.stringify(body)
    }

    let response: Response
    try {
      response = await fetcher(new URL(endpoint, baseUrl).href, init)
    } catch (error) {
      throw new ApiError(
        error instanceof Error ? error.message : 'Network error',
        0
      )
    }

    let envelope: unknown
    try {
      envelope = await response.json()
    } catch {
      throw new ApiError('The server did not answer with JSON', response.status)
    }
    if (!isEnvelope(envelope)) {
      throw new ApiError('Unexpected response', response.status)
    }
    return envelope as ApiEnvelope<T>
  }

  return {
    get: (endpoint) => send('GET', endpoint),
    post: (endpoint, body) => send('POST', endpoint, body)
  }
}

function isEnvelope(value: unknown): value is ApiEnvelope {
  return (
    typeof value === 'object' &&
    value !== null &&
    typeof (value as { ok?: unknown }).ok === 'boolean' &&
    typeof (value as { message?: unknown }).message === 'string'
  )
}
