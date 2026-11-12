import { safe } from './core/safe'
import { attachSubmitShortcut } from './editor/submit'

/** Attribute set on the root element once the plugin bundle is running. */
export const READY_ATTRIBUTE = 'data-ticketboost'

/**
 * Starts the plugin in the given document. Never throws. The leader panel starts from the
 * server bootstrap payload (Phase 2); the Ctrl+Enter shortcut needs no configuration.
 * @param doc The document to boot in.
 * @returns True when the plugin started.
 */
export function boot(doc: Document): boolean {
  return safe(() => {
    attachSubmitShortcut(doc)
    doc.documentElement.setAttribute(READY_ATTRIBUTE, 'ready')
    return true
  }, false)
}
