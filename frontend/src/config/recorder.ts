import { formatBinding, normalizeBinding } from '../keymap/binding'

/** The parts of a keyboard event the recorder reads. */
export type RecordedKey = Pick<
  KeyboardEvent,
  'key' | 'code' | 'ctrlKey' | 'altKey' | 'shiftKey' | 'metaKey'
>

const MODIFIER_KEYS = /^(Control|Alt|Shift|Meta)(Left|Right)$/

/**
 * Turns a key press into the binding text stored in the keymap. A modifier on its own is not
 * a shortcut yet, so it records nothing and the recorder keeps waiting. A printable symbol
 * pressed alone (`?`) is stored as the typed character, as the panel matches it; anything
 * else is stored as the physical key with its modifiers (`Ctrl+Shift+KeyK`).
 * @param event The key press.
 * @returns The canonical binding text, or null when the press is not a usable binding.
 */
export function recordBinding(event: RecordedKey): string | null {
  if (MODIFIER_KEYS.test(event.code)) {
    return null
  }
  const plain = !event.ctrlKey && !event.altKey && !event.metaKey
  if (plain && /^\S$/u.test(event.key) && !/^[A-Za-z0-9]$/.test(event.key)) {
    return normalizeBinding(event.key)
  }
  return normalizeBinding(
    formatBinding({
      ctrl: event.ctrlKey,
      alt: event.altKey,
      shift: event.shiftKey,
      meta: event.metaKey,
      code: event.code
    })
  )
}
