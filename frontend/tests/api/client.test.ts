import { beforeEach, describe, expect, it, vi } from 'vitest'

import type { FetchLike } from '../../src/api/client'
import { ApiError, createApi, csrfToken } from '../../src/api/client'

const BASE = 'https://glpi.test/plugins/ticketboost/ajax/'

function json(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), { status })
}

function setup(response: () => Promise<Response>) {
  const fetcher = vi.fn<FetchLike>(response)
  return { fetcher, api: createApi(document, BASE, fetcher) }
}

describe('csrfToken', () => {
  beforeEach(() => {
    document.head.replaceChildren()
  })

  it('reads the token GLPI 11 publishes and tolerates its absence', () => {
    expect(csrfToken(document)).toBeNull()

    const meta = document.createElement('meta')
    meta.setAttribute('property', 'glpi:csrf_token')
    meta.setAttribute('content', 'abc123')
    document.head.append(meta)

    expect(csrfToken(document)).toBe('abc123')
  })
})

describe('createApi', () => {
  beforeEach(() => {
    document.head.replaceChildren()
  })

  it('sends GET requests with the AJAX header and no body', async () => {
    const { api, fetcher } = setup(() =>
      Promise.resolve(json({ ok: true, message: '', data: { a: 1 } }))
    )

    const envelope = await api.get<{ a: number }>('bootstrap.php')

    expect(envelope.data.a).toBe(1)
    const [url, init] = fetcher.mock.calls[0]!
    expect(url).toBe(`${BASE}bootstrap.php`)
    expect(init.method).toBe('GET')
    expect(init.credentials).toBe('same-origin')
    expect(init.cache).toBe('no-store')
    expect(init.body).toBeUndefined()
    expect(init.headers).toEqual({
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    })
  })

  it('adds the CSRF token header when the page has one', async () => {
    const meta = document.createElement('meta')
    meta.setAttribute('property', 'glpi:csrf_token')
    meta.setAttribute('content', 'tok')
    document.head.append(meta)
    const { api, fetcher } = setup(() =>
      Promise.resolve(json({ ok: true, message: '', data: null }))
    )

    await api.get('bootstrap.php')

    expect(fetcher.mock.calls[0]![1].headers).toMatchObject({
      'X-Glpi-Csrf-Token': 'tok'
    })
  })

  it('posts JSON', async () => {
    const { api, fetcher } = setup(() =>
      Promise.resolve(json({ ok: true, message: 'Done', data: null }))
    )

    const envelope = await api.post('action.php', { action: 'next' })

    expect(envelope.message).toBe('Done')
    const init = fetcher.mock.calls[0]![1]
    expect(init.method).toBe('POST')
    expect(init.body).toBe('{"action":"next"}')
    expect(init.headers).toMatchObject({ 'Content-Type': 'application/json' })
  })

  it('returns the envelope of an error answer so the caller can show its message', async () => {
    const { api } = setup(() =>
      Promise.resolve(
        json({ ok: false, message: 'Not allowed', data: null }, 403)
      )
    )

    expect(await api.get('x.php')).toMatchObject({
      ok: false,
      message: 'Not allowed'
    })
  })

  it('wraps network failures in an ApiError with status 0', async () => {
    const { api } = setup(() => Promise.reject(new Error('offline')))

    const error: unknown = await api.get('x.php').catch((e: unknown) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ message: 'offline', status: 0 })
  })

  it('wraps non-error rejections too', async () => {
    const { api } = setup(() => Promise.reject(new Error()))
    const failing = createApi(document, BASE, () =>
      // eslint-disable-next-line @typescript-eslint/prefer-promise-reject-errors
      Promise.reject('boom')
    )

    expect(await api.get('x.php').catch((e: unknown) => e)).toBeInstanceOf(
      ApiError
    )
    expect(await failing.get('x.php').catch((e: unknown) => e)).toMatchObject({
      message: 'Network error',
      status: 0
    })
  })

  it('rejects answers that are not JSON, such as a GLPI error page', async () => {
    const { api } = setup(() =>
      Promise.resolve(new Response('<html>401</html>', { status: 401 }))
    )

    expect(await api.get('x.php').catch((e: unknown) => e)).toMatchObject({
      message: 'The server did not answer with JSON',
      status: 401
    })
  })

  it.each([null, 'text', { ok: 'yes', message: '' }, { ok: true }])(
    'rejects JSON that is not an envelope: %j',
    async (body) => {
      const { api } = setup(() => Promise.resolve(json(body)))

      expect(await api.get('x.php').catch((e: unknown) => e)).toMatchObject({
        message: 'Unexpected response',
        status: 200
      })
    }
  )
})
