import type { Binding } from './binding'
import { formatBinding, parseBinding } from './binding'
import { findAction } from './catalog'

/**
 * Chords that cannot be used: the browser never delivers them to the page, GLPI binds
 * them natively, or the panel needs them. Mirrored by the PHP validator in Phase 2.
 */
export const RESERVED_BINDINGS: readonly string[] = [
  'Ctrl+KeyW',
  'Ctrl+KeyT',
  'Ctrl+KeyN',
  'Ctrl+Tab',
  'Ctrl+Shift+KeyT',
  'Ctrl+Shift+KeyN',
  'Ctrl+Shift+KeyW',
  'Ctrl+Alt+KeyG',
  'Ctrl+Alt+KeyE',
  'Ctrl+ArrowLeft',
  'Ctrl+ArrowRight',
  'Escape',
  'Tab',
  'Shift+Tab',
  'Backspace'
]

/** Problem codes; the UI maps them to translated messages. */
export type KeymapIssueCode =
  'unknown_action' | 'invalid_binding' | 'reserved_binding' | 'conflict'

/** One problem found in a keymap. */
export interface KeymapIssue {
  readonly code: KeymapIssueCode
  readonly action: string
  readonly binding?: string
  /** For conflicts: the other actions that use the same binding. */
  readonly with?: readonly string[]
}

/**
 * Validates a keymap: known actions, parseable bindings, no reserved chords, and no
 * binding shared by two actions (or listed twice for one action).
 * @param keymap The keymap to validate; keys are untrusted input.
 * @returns Every issue found; empty when the keymap is valid.
 */
export function validateKeymap(
  keymap: Readonly<Record<string, readonly string[] | undefined>>
): KeymapIssue[] {
  const issues: KeymapIssue[] = []
  const owners = new Map<string, string[]>()
  for (const [action, bindings] of Object.entries(keymap)) {
    if (findAction(action) === undefined) {
      issues.push({ code: 'unknown_action', action })
      continue
    }
    for (const text of bindings ?? []) {
      const binding = parseBinding(text)
      if (binding === null) {
        issues.push({ code: 'invalid_binding', action, binding: text })
        continue
      }
      const canonical = formatBinding(binding)
      if (RESERVED_BINDINGS.includes(canonical)) {
        issues.push({ code: 'reserved_binding', action, binding: canonical })
        continue
      }
      owners.set(canonical, [...(owners.get(canonical) ?? []), action])
    }
  }
  for (const [binding, actions] of owners) {
    if (actions.length > 1) {
      for (const action of new Set(actions)) {
        const others = actions.filter((other) => other !== action)
        issues.push({
          code: 'conflict',
          action,
          binding,
          with: others.length > 0 ? [...new Set(others)] : [action]
        })
      }
    }
  }
  return issues
}

/**
 * Parses the bindings of one action, dropping invalid and reserved ones.
 * @param bindings Binding texts.
 * @returns Usable bindings in their original order.
 */
export function usableBindings(
  bindings: readonly string[] | undefined
): Binding[] {
  return (bindings ?? []).flatMap((text) => {
    const binding = parseBinding(text)
    return binding !== null &&
      !RESERVED_BINDINGS.includes(formatBinding(binding))
      ? [binding]
      : []
  })
}
