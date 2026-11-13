import type { Api } from './api/client'
import type { App } from './app'
import { startApp } from './app'
import { parseBootstrap } from './bootstrap'
import { detectContext } from './context/detect'
import { report, safe } from './core/safe'
import { attachSubmitShortcut } from './editor/submit'
import { findAction } from './keymap/catalog'

/** Attribute set on the root element once the plugin bundle is running. */
export const READY_ATTRIBUTE = 'data-ticketboost'

/** Attribute with the panel state: `ready` once it is running, `off` when it is not available. */
export const PANEL_ATTRIBUTE = 'data-ticketboost-panel'

/** Endpoint, relative to the plugin `ajax/` directory, that starts the panel. */
export const BOOTSTRAP_ENDPOINT = 'bootstrap.php'

/** What `boot` needs from the outside world. */
export interface BootOptions {
  /** Client for the plugin endpoints; without it only Ctrl+Enter is active. */
  readonly api?: Api
}

/**
 * Fetches the bootstrap payload and starts the panel with the actions that make sense on
 * the current page. Resolves to null, and starts nothing, when the shortcuts are disabled
 * for the user or the payload is not usable.
 * @param doc The page document.
 * @param api Client for the plugin endpoints.
 * @returns The running app, or null.
 */
export async function startPanel(doc: Document, api: Api): Promise<App | null> {
  const envelope = await api.get(BOOTSTRAP_ENDPOINT)
  const config = envelope.ok ? parseBootstrap(envelope.data) : null
  if (config === null) {
    return null
  }
  const location = doc.defaultView?.location
  const context = location ? detectContext(location) : 'other'
  const available = config.available.filter(
    (id) => context === 'ticket' || findAction(id)?.context === 'anywhere'
  )
  return startApp(
    doc,
    { ...config, available },
    {
      run: (id) => {
        throw new Error(`The action ${id} is not available yet`)
      }
    }
  )
}

/**
 * Starts the plugin in the given document. Never throws. Ctrl+Enter works at once; the
 * panel starts when the server confirms it is enabled for the user.
 * @param doc The document to boot in.
 * @param options Outside dependencies.
 * @returns True when the plugin started.
 */
export function boot(doc: Document, options: BootOptions = {}): boolean {
  return safe(() => {
    attachSubmitShortcut(doc)
    doc.documentElement.setAttribute(READY_ATTRIBUTE, 'ready')
    const api = options.api
    if (api !== undefined) {
      startPanel(doc, api)
        .then((app) => {
          doc.documentElement.setAttribute(
            PANEL_ATTRIBUTE,
            app === null ? 'off' : 'ready'
          )
        })
        .catch(report)
    }
    return true
  }, false)
}
