import type { Api } from '../api/client'
import type { ActionHandlers } from '../app'
import { ticketId } from '../context/detect'
import type { ActionId } from '../keymap/catalog'
import { showToast } from '../ui/toast'

/** Endpoint, relative to the plugin `ajax/` directory, that resolves the navigation actions. */
export const NAVIGATE_ENDPOINT = 'navigate.php'

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
 * Creates the handlers that run the panel actions against the plugin endpoints.
 * Navigation actions ask the server for the address to open and then go there; a refusal
 * from the server is shown as a toast with its own translated message.
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

  return {
    run: async (id) => {
      if (!NAVIGATION_ACTIONS.includes(id)) {
        throw new Error(`The action ${id} is not available yet`)
      }
      const query = new URLSearchParams({ to: id })
      if (id === 'next' || id === 'previous') {
        const current = win ? ticketId(win.location) : null
        if (current === null) {
          throw new Error(`The action ${id} needs a ticket on screen`)
        }
        query.set('id', String(current))
      }

      const envelope = await api.get(`${NAVIGATE_ENDPOINT}?${query.toString()}`)
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
  }
}
