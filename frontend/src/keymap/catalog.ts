/** Every MVP action (PLAN.md section 3.3). Removing or renaming one is a breaking change. */
export const ACTION_IDS = [
  'next',
  'previous',
  'list',
  'my_tickets',
  'assign_me',
  'assign_other',
  'unassign_me',
  'unassign_others',
  'change_status',
  'close',
  'followup',
  'task',
  'change_priority',
  'help'
] as const

/** An action identifier, shared with the PHP action registry. */
export type ActionId = (typeof ACTION_IDS)[number]

/** Panel group an action is listed under. */
export type ActionGroup = 'navigate' | 'assign' | 'ticket' | 'general'

/**
 * How the panel reacts when the action key is pressed: run it, open a submenu of
 * options, open a search picker, or show the shortcut overview.
 */
export type ActionKind = 'run' | 'submenu' | 'picker' | 'multi_picker' | 'help'

/** Static description of an action; labels come from the server payload. */
export interface ActionDefinition {
  readonly id: ActionId
  readonly group: ActionGroup
  readonly kind: ActionKind
  /** `ticket`: only on a ticket form; `anywhere`: on every page. */
  readonly context: 'ticket' | 'anywhere'
  readonly defaultKeys: readonly string[]
}

/** Action id to bindings (canonical text, see `keymap/binding.ts`). */
export type Keymap = Readonly<Partial<Record<ActionId, readonly string[]>>>

/** Group display order in the panel. */
export const GROUP_ORDER: readonly ActionGroup[] = [
  'navigate',
  'assign',
  'ticket',
  'general'
]

/** Static catalog; Phase 2 replaces availability with the rights-filtered server list. */
export const CATALOG: readonly ActionDefinition[] = [
  def('next', 'navigate', 'run', 'ticket', ['KeyN', 'ArrowRight']),
  def('previous', 'navigate', 'run', 'ticket', ['KeyP', 'ArrowLeft']),
  def('list', 'navigate', 'run', 'ticket', ['KeyL']),
  def('my_tickets', 'navigate', 'run', 'anywhere', ['KeyM']),
  def('assign_me', 'assign', 'run', 'ticket', ['KeyA']),
  def('assign_other', 'assign', 'picker', 'ticket', ['KeyO']),
  def('unassign_me', 'assign', 'run', 'ticket', ['KeyU']),
  def('unassign_others', 'assign', 'multi_picker', 'ticket', ['KeyX']),
  def('change_status', 'ticket', 'submenu', 'ticket', ['KeyS']),
  def('close', 'ticket', 'run', 'ticket', ['KeyC']),
  def('followup', 'ticket', 'run', 'ticket', ['KeyF']),
  def('task', 'ticket', 'run', 'ticket', ['KeyT']),
  def('change_priority', 'ticket', 'submenu', 'ticket', ['KeyR']),
  def('help', 'general', 'help', 'anywhere', ['?'])
]

/**
 * The default keymap derived from the catalog.
 * @returns A fresh keymap with every action on its default keys.
 */
export function defaultKeymap(): Keymap {
  return Object.fromEntries(
    CATALOG.map((action) => [action.id, [...action.defaultKeys]])
  )
}

/**
 * Applies overrides on top of a base keymap. An override replaces the whole binding list
 * of its action; an empty list disables the action.
 * @param base The base keymap (admin defaults).
 * @param overrides Per-action replacements (user preferences).
 * @returns The effective keymap.
 */
export function mergeKeymap(base: Keymap, overrides: Keymap): Keymap {
  return { ...base, ...overrides }
}

/**
 * Finds the static definition of an action.
 * @param id The action id.
 * @returns The definition, or undefined for an unknown id.
 */
export function findAction(id: string): ActionDefinition | undefined {
  return CATALOG.find((action) => action.id === id)
}

function def(
  id: ActionId,
  group: ActionGroup,
  kind: ActionKind,
  context: 'ticket' | 'anywhere',
  defaultKeys: readonly string[]
): ActionDefinition {
  return { id, group, kind, context, defaultKeys }
}
