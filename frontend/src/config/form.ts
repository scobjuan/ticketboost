import { safe } from '../core/safe'
import { checkFields, type ConfigMessages } from './check'
import { recordBinding } from './recorder'

/** Attribute that marks the configuration form. */
export const FORM_SELECTOR = 'form[data-tb-config]'

/** Stops a running configuration form. */
export interface ConfigFormHandle {
  readonly destroy: () => void
}

/**
 * Reads the translated texts the page left on the form.
 * @param form The configuration form.
 * @returns The texts, or null when they are missing or malformed.
 */
export function readMessages(form: HTMLElement): ConfigMessages | null {
  const raw = form.dataset.tbMessages
  if (raw === undefined) {
    return null
  }
  const data = safe<unknown>(() => JSON.parse(raw), null)
  if (typeof data !== 'object' || data === null) {
    return null
  }
  const { recording, invalid, reserved, conflict, actions } = data as Record<
    string,
    unknown
  >
  if (
    typeof recording === 'string' &&
    typeof invalid === 'string' &&
    typeof reserved === 'string' &&
    typeof conflict === 'string' &&
    typeof actions === 'object' &&
    actions !== null
  ) {
    return {
      recording,
      invalid,
      reserved,
      conflict,
      actions: actions as Record<string, string>
    }
  }
  return null
}

/**
 * Makes the shortcut fields of the administration page checkable and recordable: every
 * change is checked at once, and the Record button of a field fills it with the next key
 * pressed. The server checks again when the form is saved.
 * @param doc The page document.
 * @returns A handle that stops the form, or null when the form was not found.
 */
export function startConfigForm(doc: Document): ConfigFormHandle | null {
  const form = doc.querySelector<HTMLFormElement>(FORM_SELECTOR)
  const messages = form ? readMessages(form) : null
  if (!form || messages === null) {
    return null
  }
  const stopListening = new AbortController()
  const rows = [...form.querySelectorAll<HTMLElement>('[data-tb-action]')]

  const refresh = (note?: { row: HTMLElement; text: string }): void => {
    const values: Record<string, string> = {}
    for (const row of rows) {
      const input = row.querySelector<HTMLInputElement>('[data-tb-binding]')
      values[row.dataset.tbAction ?? ''] = input?.value ?? ''
    }
    const problems = checkFields(values, messages)
    for (const row of rows) {
      const found = problems[row.dataset.tbAction ?? ''] ?? []
      const feedback = row.querySelector<HTMLElement>('[data-tb-feedback]')
      if (feedback) {
        feedback.textContent = note?.row === row ? note.text : found.join('. ')
      }
      row
        .querySelector('[data-tb-binding]')
        ?.classList.toggle('is-invalid', found.length > 0)
    }
  }

  let recording: HTMLElement | null = null
  const stop = (): void => {
    recording?.querySelector('[data-tb-record]')?.classList.remove('active')
    recording = null
    refresh()
  }

  doc.addEventListener(
    'keydown',
    (event) => {
      safe(() => {
        if (recording === null) {
          return
        }
        event.preventDefault()
        event.stopImmediatePropagation()
        if (event.key === 'Escape') {
          stop()
          return
        }
        const binding = recordBinding(event)
        const input =
          recording.querySelector<HTMLInputElement>('[data-tb-binding]')
        if (binding !== null && input) {
          input.value = binding
          stop()
        }
      }, undefined)
    },
    { capture: true, signal: stopListening.signal }
  )

  for (const row of rows) {
    const { signal } = stopListening
    row.querySelector('[data-tb-binding]')?.addEventListener(
      'input',
      () => {
        refresh()
      },
      { signal }
    )
    row.querySelector('[data-tb-record]')?.addEventListener(
      'click',
      () => {
        stop()
        recording = row
        row.querySelector('[data-tb-record]')?.classList.add('active')
        refresh({ row, text: messages.recording })
      },
      { signal }
    )
  }
  refresh()
  return {
    destroy: () => {
      stopListening.abort()
    }
  }
}
