import { defaultKeymap } from '../keymap/catalog'
import { validateKeymap } from '../keymap/validator'

/** Translated texts the page hands over; `%s`, `%1$s` and `%2$s` mark where values go. */
export interface ConfigMessages {
  readonly recording: string
  readonly invalid: string
  readonly reserved: string
  readonly conflict: string
  /** Action id to its translated name. */
  readonly actions: Readonly<Record<string, string>>
}

/**
 * Fills the placeholders of a translated text.
 * @param template Text with `%s` or numbered `%1$s` placeholders.
 * @param values Values for the placeholders, in order.
 * @returns The text with the values in place.
 */
export function format(template: string, ...values: string[]): string {
  let next = 0
  return template.replace(/%(?:(\d)\$)?s/g, (_match, position?: string) => {
    const index = position === undefined ? next++ : Number(position) - 1
    return values[index] ?? ''
  })
}

/**
 * Splits what was typed in a shortcut field into bindings.
 * @param text The field text, such as `Ctrl+KeyK, KeyN`.
 * @returns The bindings, without empty parts.
 */
export function splitBindings(text: string): string[] {
  return text.split(/[\s,]+/).filter((part) => part !== '')
}

/**
 * Checks the shortcut fields the way the server does: a field left empty keeps the binding
 * that applies without it, a disabled action has none, and the result must have no invalid,
 * reserved or repeated shortcut.
 * @param fields Action id to the text typed for it, or null when the action is disabled.
 * @param messages Translated texts.
 * @param base Action id to the text that applies to an empty field; catalog defaults otherwise.
 * @returns Action id to the problems found for it; actions without problems are absent.
 */
export function checkFields(
  fields: Readonly<Record<string, string | null>>,
  messages: ConfigMessages,
  base: Readonly<Record<string, string>> = {}
): Record<string, string[]> {
  const keymap: Record<string, readonly string[]> = { ...defaultKeymap() }
  for (const [action, text] of Object.entries(base)) {
    keymap[action] = splitBindings(text)
  }
  for (const [action, text] of Object.entries(fields)) {
    const bindings = text === null ? [] : splitBindings(text)
    if (text === null || bindings.length > 0) {
      keymap[action] = bindings
    }
  }
  const problems: Record<string, string[]> = {}
  for (const issue of validateKeymap(keymap)) {
    const binding = issue.binding ?? ''
    const text =
      issue.code === 'reserved_binding'
        ? format(messages.reserved, binding)
        : issue.code === 'conflict'
          ? format(
              messages.conflict,
              binding,
              (issue.with ?? [])
                .map((other) => messages.actions[other] ?? other)
                .join(', ')
            )
          : format(messages.invalid, binding)
    ;(problems[issue.action] ??= []).push(text)
  }
  return problems
}
