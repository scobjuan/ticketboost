import type { KeyLike } from '../keys/leader'

/**
 * A key binding. Either a physical key (`code`, from `KeyboardEvent.code`, layout
 * independent) or a typed character (`char`, from `KeyboardEvent.key`) for symbols such
 * as `?` whose physical key differs between layouts.
 */
export interface Binding {
  readonly ctrl: boolean
  readonly alt: boolean
  readonly shift: boolean
  readonly meta: boolean
  readonly code?: string
  readonly char?: string
}

type Flag = 'ctrl' | 'alt' | 'shift' | 'meta'

const MODIFIERS: readonly (readonly [string, Flag])[] = [
  ['Ctrl', 'ctrl'],
  ['Alt', 'alt'],
  ['Shift', 'shift'],
  ['Meta', 'meta']
]

const FLAGS: Readonly<Record<string, Flag>> = Object.fromEntries(MODIFIERS)

const CODE_PATTERN =
  /^(Key[A-Z]|Digit\d|Numpad\d|F([1-9]|1[0-2])|Arrow(Up|Down|Left|Right)|Enter|Space|Backspace|Tab|Escape|Delete|Insert|Home|End|PageUp|PageDown|Minus|Equal|Comma|Period|Slash|Semicolon|Quote|Backquote|Backslash|BracketLeft|BracketRight)$/

const GLYPHS: Readonly<Record<string, string>> = {
  ArrowUp: '↑',
  ArrowDown: '↓',
  ArrowLeft: '←',
  ArrowRight: '→',
  Escape: 'Esc',
  Space: 'Space',
  Enter: 'Enter'
}

/**
 * Parses a binding written as `[Ctrl+][Alt+][Shift+][Meta+]Key`, where Key is a
 * `KeyboardEvent.code` (`KeyN`, `Digit1`, `ArrowRight`) or a single printable character.
 * @param text The binding text.
 * @returns The binding, or null when the text is not a valid binding.
 */
export function parseBinding(text: string): Binding | null {
  const parts = text.split('+')
  let key = parts.pop() ?? ''
  if (key === '' && parts.length > 0 && parts[parts.length - 1] === '') {
    parts.pop()
    key = '+'
  }
  const flags = { ctrl: false, alt: false, shift: false, meta: false }
  for (const part of parts) {
    const name = FLAGS[part]
    if (name === undefined || flags[name]) {
      return null
    }
    flags[name] = true
  }
  if (CODE_PATTERN.test(key)) {
    return { ...flags, code: key }
  }
  if (/^\S$/u.test(key)) {
    return { ...flags, shift: false, char: key }
  }
  return null
}

/**
 * Writes a binding in its canonical text form, the form used to compare bindings.
 * @param binding The binding.
 * @returns Canonical text such as `Ctrl+KeyN` or `?`.
 */
export function formatBinding(binding: Binding): string {
  return [...modifierNames(binding), binding.code ?? binding.char ?? ''].join(
    '+'
  )
}

/**
 * Normalizes binding text so equal bindings compare equal.
 * @param text The binding text.
 * @returns The canonical text, or null when invalid.
 */
export function normalizeBinding(text: string): string | null {
  const binding = parseBinding(text)
  return binding ? formatBinding(binding) : null
}

/**
 * Short label for a key badge: `KeyN` -> `N`, `Digit1` -> `1`, `ArrowRight` -> an arrow.
 * @param binding The binding.
 * @returns The badge text.
 */
export function displayBinding(binding: Binding): string {
  const key = binding.char ?? keyLabel(binding.code ?? '')
  return [...modifierNames(binding), key].join('+')
}

function modifierNames(binding: Binding): string[] {
  return MODIFIERS.filter(([, flag]) => binding[flag]).map(([name]) => name)
}

function keyLabel(code: string): string {
  const glyph = GLYPHS[code]
  if (glyph !== undefined) {
    return glyph
  }
  const match = /^(?:Key|Digit|Numpad)(.)$/.exec(code)
  return match?.[1] ?? code
}

/**
 * Tells whether a keyboard event matches a binding. Character bindings ignore Shift,
 * because typing the character may need it on some layouts.
 * @param binding The binding.
 * @param event The keyboard event.
 * @returns True when the event matches.
 */
export function matchesBinding(binding: Binding, event: KeyLike): boolean {
  if (
    binding.ctrl !== event.ctrlKey ||
    binding.alt !== event.altKey ||
    binding.meta !== event.metaKey
  ) {
    return false
  }
  if (binding.char !== undefined) {
    return event.key === binding.char
  }
  return binding.shift === event.shiftKey && binding.code === event.code
}
