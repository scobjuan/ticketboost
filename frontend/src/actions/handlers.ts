import type { Api, ApiEnvelope } from '../api/client'
import type { ActionHandlers } from '../app'
import { ticketId } from '../context/detect'
import { openComposer } from '../editor/compose'
import type { ActionId } from '../keymap/catalog'
import type { PickerItem } from '../ui/modal'
import { showToast } from '../ui/toast'

/** Endpoint, relative to the plugin `ajax/` directory, that resolves the navigation actions. */
export const NAVIGATE_ENDPOINT = 'navigate.php'

/** Endpoint that runs the state-changing actions. */
export const ACTION_ENDPOINT = 'action.php'

/** Endpoint that lists who the assignment pickers offer. */
export const SEARCH_ENDPOINT = 'search.php'

/** Actions the server runs on the ticket on screen, then the ticket reloads. */
const SERVER_ACTIONS: readonly ActionId[] = [
  'assign_me',
  'assign_other',
  'unassign_me',
  'unassign_others',
  'change_status',
  'change_priority',
  'close'
]

const NAVIGATION_ACTIONS: readonly ActionId[] = [
  'next',
  'previous',
  'list',
  'my_tickets'
]

/**
 * Accepts an address only when it stays on the current site, so a server answer can never
 * send the user elsewhere.
 * @param location The page location.
 * @param target The address the server asked to open.
 * @returns The absolute address, or null when it points to another origin or is invalid.
 */
export function sameOriginUrl(
  location: Pick<Location, 'href' | 'origin'>,
  target: string
): string | null {
  try {
    const url = new URL(target, location.href)
    return url.origin === location.origin ? url.href : null
  } catch {
    return null
  }
}

/**
 * Reads the people a picker offers from a search answer, dropping anything malformed.
 * @param data The `data` of the search envelope.
 * @returns The picker items.
 */
export function pickerItems(data: unknown): PickerItem[] {
  if (!Array.isArray(data)) {
    return []
  }
  const items: PickerItem[] = []
  for (const entry of data as unknown[]) {
    if (typeof entry !== 'object' || entry === null) {
      continue
    }
    const { id, label, detail } = entry as Record<string, unknown>
    if (typeof id === 'string' && typeof label === 'string') {
      items.push(
        typeof detail === 'string' ? { id, label, detail } : { id, label }
      )
    }
  }
  return items
}

/**
 * Creates the handlers that run the panel actions against the plugin endpoints.
 * Navigation actions ask the server for the address to open and then go there; ticket
 * actions (assign, status, priority, close) are posted and, once done, reload the ticket. A refusal from the server is shown
 * as a toast with its own translated message.
 * @param doc The page document.
 * @param api Client for the plugin endpoints.
 * @param navigate Opens an address; defaults to a normal page navigation.
 * @returns The action handlers.
 */
export function createActionHandlers(
  doc: Document,
  api: Api,
  navigate?: (url: string) => void
): ActionHandlers {
  const win = doc.defaultView
  const go =
    navigate ??
    ((url: string): void => {
      win?.location.assign(url)
    })

  const currentTicket = (id: ActionId): number => {
    const current = win ? ticketId(win.location) : null
    if (current === null) {
      throw new Error(`The action ${id} needs a ticket on screen`)
    }
    return current
  }

  const follow = (envelope: ApiEnvelope): void => {
    if (!envelope.ok && envelope.message !== '') {
      showToast(doc, envelope.message, 'error')
      return
    }
    const url =
      envelope.ok && envelope.redirect !== undefined && win
        ? sameOriginUrl(win.location, envelope.redirect)
        : null
    if (url === null) {
      throw new Error('The server did not give an address to open')
    }
    go(url)
  }

  return {
    run: async (id, value) => {
      if (id === 'followup' || id === 'task') {
        if (!(await openComposer(doc, id))) {
          throw new Error(`The ${id} form is not on this page`)
        }
        return
      }
      if (SERVER_ACTIONS.includes(id)) {
        const body: Record<string, unknown> = {
          action: id,
          id: currentTicket(id)
        }
        if (value !== undefined) {
          body.value = value
        }
        follow(await api.post(ACTION_ENDPOINT, body))
        return
      }
      if (!NAVIGATION_ACTIONS.includes(id)) {
        throw new Error(`The action ${id} is not available yet`)
      }
      const query = new URLSearchParams({ to: id })
      if (id === 'next' || id === 'previous') {
        query.set('id', String(currentTicket(id)))
      }
      follow(await api.get(`${NAVIGATE_ENDPOINT}?${query.toString()}`))
    },
    search: async (id, query) => {
      const params = new URLSearchParams({
        action: id,
        id: String(currentTicket(id)),
        q: query
      })
      const envelope = await api.get(`${SEARCH_ENDPOINT}?${params.toString()}`)
      if (!envelope.ok) {
        throw new Error(envelope.message)
      }
      return pickerItems(envelope.data)
    }
  }
}
