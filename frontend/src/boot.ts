import { safe } from './core/safe'

/** Attribute set on the root element once the plugin bundle is running. */
export const READY_ATTRIBUTE = 'data-ticketboost'

/**
 * Starts the plugin in the given document. Never throws.
 * @param doc The document to boot in.
 * @returns True when the plugin started.
 */
export function boot(doc: Document): boolean {
  return safe(() => {
    doc.documentElement.setAttribute(READY_ATTRIBUTE, 'ready')
    return true
  }, false)
}
