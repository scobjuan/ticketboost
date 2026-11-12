import { h } from '../core/dom'

/** Visual and semantic kind of a toast. */
export type ToastKind = 'success' | 'error' | 'info'

const CONTAINER_CLASS = 'tb-toasts'

/**
 * Shows a short message at the bottom of the page. Errors are announced assertively.
 * @param doc The document to show the toast in.
 * @param message Translated text.
 * @param kind Toast kind.
 * @param durationMs Time before the toast is removed.
 * @returns The toast element.
 */
export function showToast(
  doc: Document,
  message: string,
  kind: ToastKind = 'info',
  durationMs = 4000
): HTMLElement {
  let container = doc.querySelector<HTMLElement>(`.${CONTAINER_CLASS}`)
  if (container === null) {
    container = h(doc, 'div', { class: CONTAINER_CLASS, 'aria-live': 'polite' })
    doc.body.appendChild(container)
  }
  const toast = h(
    doc,
    'div',
    {
      class: `tb-toast tb-toast--${kind}`,
      role: kind === 'error' ? 'alert' : 'status'
    },
    [message]
  )
  container.appendChild(toast)
  setTimeout(() => {
    toast.remove()
  }, durationMs)
  return toast
}
