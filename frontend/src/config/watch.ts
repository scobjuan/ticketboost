import { safe } from '../core/safe'
import { type ConfigFormHandle, FORM_SELECTOR, startConfigForm } from './form'

/**
 * Starts every configuration form of the page, including the ones that arrive later: GLPI
 * loads tab content by AJAX and runs this module only once. A form that leaves the page is
 * stopped so its key listener goes with it.
 * @param doc The page document.
 * @returns A handle that stops watching and stops every form.
 */
export function watchConfigForms(doc: Document): ConfigFormHandle {
  const running = new Map<HTMLFormElement, ConfigFormHandle>()

  const scan = (): void => {
    for (const [form, handle] of running) {
      if (!form.isConnected) {
        handle.destroy()
        running.delete(form)
      }
    }
    for (const form of doc.querySelectorAll<HTMLFormElement>(FORM_SELECTOR)) {
      if (!running.has(form)) {
        const handle = startConfigForm(doc, form)
        if (handle) {
          running.set(form, handle)
        }
      }
    }
  }

  const observer = new MutationObserver(() => {
    safe(scan, undefined)
  })
  observer.observe(doc.documentElement, { childList: true, subtree: true })
  scan()

  return {
    destroy: () => {
      observer.disconnect()
      for (const handle of running.values()) {
        handle.destroy()
      }
      running.clear()
    }
  }
}
