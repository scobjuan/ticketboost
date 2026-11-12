import type { AppConfig } from './app'
import type { Labels } from './i18n/translate'
import type { ActionId, Keymap } from './keymap/catalog'
import { ACTION_IDS } from './keymap/catalog'
import type { LeaderConfig, LeaderKey } from './keys/leader'
import { DEFAULT_LEADER } from './keys/leader'

const LEADER_KEYS: readonly string[] = ['Control', 'Alt', 'Shift', 'Meta']

/**
 * Turns the server bootstrap payload into the app configuration. The payload crosses a
 * trust boundary, so every field is checked; anything malformed disables the panel
 * instead of breaking the page.
 * @param data The `data` of the bootstrap envelope.
 * @returns The configuration, or null when the shortcuts are disabled or the payload is invalid.
 */
export function parseBootstrap(data: unknown): AppConfig | null {
  if (!isRecord(data) || data.enabled !== true) {
    return null
  }
  const leader = parseLeader(data.leader)
  const keymap = parseKeymap(data.keymap)
  const labels = parseLabels(data.labels)
  const available = parseAvailable(data.available)
  if (leader === null || keymap === null || labels === null) {
    return null
  }
  return { leader, keymap, labels, available }
}

function parseLeader(value: unknown): LeaderConfig | null {
  if (!isRecord(value)) {
    return null
  }
  const key = value.key
  const mode = value.mode
  if (
    typeof key !== 'string' ||
    !LEADER_KEYS.includes(key) ||
    (mode !== 'tap' && mode !== 'hold')
  ) {
    return null
  }
  const number = (name: keyof LeaderConfig, fallback: number): number => {
    const field = value[name]
    return typeof field === 'number' && Number.isFinite(field) && field > 0
      ? field
      : fallback
  }
  return {
    key: key as LeaderKey,
    mode,
    taps: number('taps', DEFAULT_LEADER.taps),
    tapMaxMs: number('tapMaxMs', DEFAULT_LEADER.tapMaxMs),
    tapWindowMs: number('tapWindowMs', DEFAULT_LEADER.tapWindowMs),
    holdMs: number('holdMs', DEFAULT_LEADER.holdMs)
  }
}

function parseKeymap(value: unknown): Keymap | null {
  if (!isRecord(value)) {
    return null
  }
  const keymap: Partial<Record<ActionId, string[]>> = {}
  for (const [action, bindings] of Object.entries(value)) {
    if (!isActionId(action)) {
      continue
    }
    if (!Array.isArray(bindings) || !bindings.every(isString)) {
      return null
    }
    keymap[action] = bindings
  }
  return keymap
}

function parseLabels(value: unknown): Labels | null {
  if (!isRecord(value)) {
    return null
  }
  return Object.fromEntries(
    Object.entries(value).filter((entry): entry is [string, string] =>
      isString(entry[1])
    )
  )
}

function parseAvailable(value: unknown): ActionId[] {
  return Array.isArray(value) ? value.filter(isActionId) : []
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function isString(value: unknown): value is string {
  return typeof value === 'string'
}

function isActionId(value: unknown): value is ActionId {
  return ACTION_IDS.some((id) => id === value)
}
