import { safe } from '../core/safe'
import { tinymceOf } from './tinymce'

/** Followup and task forms of the ticket timeline, matched by their form action. */
const TIMELINE_FORM_ACTION = /\/(itilfollowup|tickettask)\.form\.php(\?|$)/

/**
 * Finds the timeline followup or task form that Ctrl+Enter should send, if any.
 * GLPI already sends the form on Ctrl+Enter inside TinyMCE and plain textareas, so those
 * targets are skipped (ADR 0016); only other fields of the form are covered here.
 * @param target The element that has focus.
 * @returns The form and its single add/update button, or null.
 */
export function submittableForm(
  target: EventTarget | null
): { form: HTMLFormElement; button: HTMLButtonElement } | null {
  if (!(target instanceof Element) || target instanceof HTMLTextAreaElement) {
    return null
  }
  const form = target.closest('form')
  if (
    form === null ||
    !TIMELINE_FORM_ACTION.test(form.getAttribute('action') ?? '')
  ) {
    return null
  }
  const buttons = [
    ...form.querySelectorAll<HTMLButtonElement>(
      'button[type="submit"][name="add"], button[type="submit"][name="update"]'
    )
  ]
  const button = buttons[0]
  return buttons.length === 1 && button !== undefined ? { form, button } : null
}

/**
 * Sends the focused followup or task form on Ctrl+Enter (Cmd+Enter on macOS) through its
 * own submit button, so GLPI's submit handlers (validation, submit once) still run.
 * @param doc The document to listen on.
 * @returns A function that removes the listener.
 */
export function attachSubmitShortcut(doc: Document): () => void {
  const listener = (event: KeyboardEvent): void => {
    safe(() => {
      if (
        event.key !== 'Enter' ||
        event.ctrlKey === event.metaKey ||
        event.altKey ||
        event.shiftKey ||
        event.isComposing
      ) {
        return
      }
      const target = submittableForm(event.target)
      if (target === null) {
        return
      }
      event.preventDefault()
      event.stopPropagation()
      const win = doc.defaultView
      if (win !== null) {
        tinymceOf(win)?.triggerSave?.()
      }
      target.form.requestSubmit(target.button)
    }, undefined)
  }
  doc.addEventListener('keydown', listener, true)
  return () => {
    doc.removeEventListener('keydown', listener, true)
  }
}
