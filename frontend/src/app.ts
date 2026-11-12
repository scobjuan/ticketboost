import { report, safe } from './core/safe'
import { watchEditors } from './editor/tinymce'
import type { Labels } from './i18n/translate'
import { createTranslator } from './i18n/translate'
import { parseBinding } from './keymap/binding'
import type { ActionDefinition, ActionId, Keymap } from './keymap/catalog'
import { CATALOG, GROUP_ORDER } from './keymap/catalog'
import { usableBindings } from './keymap/validator'
import type { LeaderConfig } from './keys/leader'
import { createLeader } from './keys/leader'
import type { MenuGroup, MenuItem, PickerItem, View } from './ui/modal'
import { createModal } from './ui/modal'
import { showToast } from './ui/toast'

/** An option of a submenu action (status, priority), already translated. */
export interface SubmenuOption {
  readonly value: string
  readonly label: string
}

/** What the server tells the frontend (bootstrap payload, Phase 2). */
export interface AppConfig {
  readonly leader: LeaderConfig
  /** The effective keymap: admin defaults merged with user overrides. */
  readonly keymap: Keymap
  /** Actions allowed for this user on this page, already filtered by rights. */
  readonly available: readonly ActionId[]
  readonly labels: Labels
  readonly options?: Readonly<
    Partial<Record<ActionId, readonly SubmenuOption[]>>
  >
}

/** Runs actions; implemented by the API client from Phase 3 on. */
export interface ActionHandlers {
  /**
   * Runs an action. `value` is the chosen submenu option, or the chosen picker ids.
   * A rejected promise shows a generic error toast.
   */
  run(id: ActionId, value?: string | readonly string[]): unknown
  /** Search source for picker actions. */
  search?(id: ActionId, query: string): Promise<readonly PickerItem[]>
}

/** The running plugin UI. */
export interface App {
  /** Opens the panel as if the leader had been pressed. */
  open(): void
  close(): void
  /** Listens for keys in another document (a TinyMCE iframe). */
  attach(doc: Document): () => void
  /** Removes every listener and closes the panel. */
  destroy(): void
}

/**
 * Starts the keyboard layer: leader detection on the page and inside every TinyMCE
 * editor, and the panel listing the available actions.
 * @param doc The page document.
 * @param config Server configuration.
 * @param handlers Action implementations.
 * @returns The running app.
 */
export function startApp(
  doc: Document,
  config: AppConfig,
  handlers: ActionHandlers
): App {
  const t = createTranslator(config.labels)
  const modal = createModal(doc, t)
  const actions = CATALOG.filter((action) =>
    config.available.includes(action.id)
  )
  const bindingsOf = (action: ActionDefinition): MenuItem['bindings'] =>
    usableBindings(config.keymap[action.id])

  const run = (id: ActionId, value?: string | readonly string[]): void => {
    modal.close()
    Promise.resolve()
      .then(() => handlers.run(id, value))
      .catch((error: unknown) => {
        report(error)
        showToast(doc, t('message.action_failed'), 'error')
      })
  }

  const submenu = (action: ActionDefinition): View => ({
    type: 'menu',
    title: t(`action.${action.id}`),
    groups: [
      {
        label: '',
        items: (config.options?.[action.id] ?? [])
          .slice(0, 9)
          .map((option, index) => ({
            id: option.value,
            label: option.label,
            bindings: digit(index + 1),
            select: () => {
              run(action.id, option.value)
            }
          }))
      }
    ]
  })

  const pickerView = (action: ActionDefinition): View => ({
    type: 'picker',
    title: t(`action.${action.id}`),
    multi: action.kind === 'multi_picker',
    source: (query) =>
      handlers.search ? handlers.search(action.id, query) : Promise.resolve([]),
    submit: (items) => {
      if (items.length === 0) {
        return
      }
      const ids = items.map((item) => item.id)
      run(action.id, action.kind === 'multi_picker' ? ids : ids[0])
    }
  })

  const groups = (overview: boolean): MenuGroup[] =>
    GROUP_ORDER.map((group) => ({
      label: t(`group.${group}`),
      items: actions
        .filter((action) => action.group === group)
        .map((action) => ({
          id: action.id,
          label: t(`action.${action.id}`),
          bindings: bindingsOf(action),
          hidden: !overview && action.kind === 'help',
          select: () => {
            select(action)
          }
        }))
        .filter((item) => item.bindings.length > 0)
    }))

  const select = (action: ActionDefinition): void => {
    switch (action.kind) {
      case 'run':
        run(action.id)
        return
      case 'submenu':
        modal.push(submenu(action))
        return
      case 'picker':
      case 'multi_picker':
        modal.push(pickerView(action))
        return
      case 'help':
        modal.push({
          type: 'menu',
          title: t('ui.all_shortcuts'),
          groups: groups(true),
          readonly: true
        })
    }
  }

  const open = (): void => {
    modal.open({ type: 'menu', title: t('ui.title'), groups: groups(false) })
  }

  const leader = createLeader(config.leader, () => {
    safe(open, undefined)
  })

  const detachers = new Set<() => void>()
  let destroyed = false

  const attach = (target: Document): (() => void) => {
    if (destroyed) {
      return () => undefined
    }
    const onKeydown = (event: KeyboardEvent): void => {
      safe(() => {
        if (!modal.isOpen()) {
          leader.keydown(event)
        } else if (modal.handleKey(event)) {
          event.preventDefault()
          event.stopPropagation()
        }
      }, undefined)
    }
    const onKeyup = (event: KeyboardEvent): void => {
      safe(() => {
        if (!modal.isOpen()) {
          leader.keyup(event)
        }
      }, undefined)
    }
    const onPointer = (): void => {
      leader.reset()
    }
    const win = target.defaultView
    target.addEventListener('keydown', onKeydown, true)
    target.addEventListener('keyup', onKeyup, true)
    target.addEventListener('mousedown', onPointer, true)
    target.addEventListener('wheel', onPointer, {
      capture: true,
      passive: true
    })
    win?.addEventListener('blur', onPointer)
    const detach = (): void => {
      target.removeEventListener('keydown', onKeydown, true)
      target.removeEventListener('keyup', onKeyup, true)
      target.removeEventListener('mousedown', onPointer, true)
      target.removeEventListener('wheel', onPointer, true)
      win?.removeEventListener('blur', onPointer)
      detachers.delete(detach)
    }
    detachers.add(detach)
    return detach
  }

  attach(doc)
  const win = doc.defaultView
  if (win !== null) {
    watchEditors(win, (editorDoc) => {
      attach(editorDoc)
    })
  }

  return {
    open,
    close: () => {
      modal.close()
    },
    attach,
    destroy: () => {
      destroyed = true
      detachers.forEach((detach) => {
        detach()
      })
      leader.reset()
      modal.close()
    }
  }
}

function digit(value: number): MenuItem['bindings'] {
  const binding = parseBinding(`Digit${String(value)}`)
  const numpad = parseBinding(`Numpad${String(value)}`)
  return [binding, numpad].filter((entry) => entry !== null)
}
