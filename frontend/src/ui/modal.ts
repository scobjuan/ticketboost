import { h, replaceChildren } from '../core/dom'
import type { Translate } from '../i18n/translate'
import type { Binding } from '../keymap/binding'
import { displayBinding, matchesBinding } from '../keymap/binding'

/** An entry of a menu view. */
export interface MenuItem {
  readonly id: string
  readonly label: string
  readonly bindings: readonly Binding[]
  /** Bound but not listed (the help shortcut, shown in the footer instead). */
  readonly hidden?: boolean
  readonly select: () => void
}

/** A labelled block of menu entries. */
export interface MenuGroup {
  readonly label: string
  readonly items: readonly MenuItem[]
}

/** A list of actions or options selected with single keys. */
export interface MenuView {
  readonly type: 'menu'
  readonly title: string
  readonly groups: readonly MenuGroup[]
  /** Overview mode: entries are listed with every binding but cannot be selected. */
  readonly readonly?: boolean
}

/** A search result in a picker. */
export interface PickerItem {
  readonly id: string
  readonly label: string
  readonly detail?: string
}

/** A search box with a result list; single or multiple selection. */
export interface PickerView {
  readonly type: 'picker'
  readonly title: string
  readonly multi: boolean
  readonly source: (query: string) => Promise<readonly PickerItem[]>
  readonly submit: (items: readonly PickerItem[]) => void
}

/** Any panel view. */
export type View = MenuView | PickerView

/** The leader panel. */
export interface Modal {
  /** Opens the panel on a root view, replacing any open stack. */
  open(view: View): void
  /** Opens a nested view (submenu, picker, overview) with a breadcrumb. */
  push(view: View): void
  close(): void
  isOpen(): boolean
  /**
   * Handles a keydown while the panel is open.
   * @returns True when the panel consumed the key; the caller must then stop the event.
   */
  handleKey(event: KeyboardEvent): boolean
}

/** Delay before a picker queries its source after typing. */
export const SEARCH_DELAY_MS = 150

const FOCUSABLE =
  'button:not([disabled]), input:not([disabled]), [tabindex="0"]'

interface PickerState {
  readonly view: PickerView
  readonly input: HTMLInputElement
  readonly list: HTMLElement
  readonly body: HTMLElement
  results: readonly PickerItem[]
  active: number
  readonly selected: Map<string, PickerItem>
  request: number
  timer: ReturnType<typeof setTimeout> | undefined
}

/**
 * Creates the panel. Nothing is added to the page until it opens.
 * @param doc The document that hosts the panel.
 * @param t Label lookup for the panel chrome (titles, hints).
 * @returns The panel controller.
 */
export function createModal(doc: Document, t: Translate): Modal {
  const stack: View[] = []
  let overlay: HTMLElement | null = null
  let panel: HTMLElement | null = null
  let restoreFocus: Element | null = null
  let picker: PickerState | null = null
  let counter = 0

  const isOpen = (): boolean => overlay !== null

  const close = (): void => {
    if (overlay === null) {
      return
    }
    clearTimeout(picker?.timer)
    picker = null
    stack.length = 0
    overlay.remove()
    overlay = null
    panel = null
    const target = restoreFocus
    restoreFocus = null
    if (target instanceof HTMLElement && target.isConnected) {
      target.focus()
    }
  }

  const ensureShell = (): HTMLElement => {
    if (panel !== null) {
      return panel
    }
    restoreFocus = doc.activeElement
    counter += 1
    panel = h(doc, 'div', {
      class: 'tb-modal',
      role: 'dialog',
      'aria-modal': 'true',
      'aria-labelledby': `tb-modal-title-${String(counter)}`,
      tabindex: '-1'
    })
    overlay = h(doc, 'div', { class: 'tb-overlay' }, [panel])
    overlay.addEventListener('mousedown', (event) => {
      if (event.target === overlay) {
        close()
      }
    })
    doc.body.appendChild(overlay)
    return panel
  }

  const render = (): void => {
    const view = stack[stack.length - 1]
    if (view === undefined) {
      return
    }
    const shell = ensureShell()
    clearTimeout(picker?.timer)
    picker = null
    const title = h(
      doc,
      'div',
      { class: 'tb-modal__title', id: `tb-modal-title-${String(counter)}` },
      [stack.map((entry) => entry.title).join(' / ')]
    )
    if (view.type === 'menu') {
      replaceChildren(shell, [title, renderMenu(view), renderFooter(view)])
      shell.focus()
      return
    }
    const state = createPicker(view)
    picker = state
    replaceChildren(shell, [title, state.body, renderFooter(view)])
    state.input.focus()
    search(state)
  }

  const renderMenu = (view: MenuView): HTMLElement =>
    h(
      doc,
      'div',
      { class: 'tb-modal__body' },
      view.groups
        .map((group) => ({
          group,
          items: group.items.filter((item) => item.hidden !== true)
        }))
        .filter(({ items }) => items.length > 0)
        .map(({ group, items }) =>
          h(doc, 'div', { class: 'tb-group' }, [
            group.label === ''
              ? null
              : h(doc, 'div', { class: 'tb-group__label' }, [group.label]),
            h(
              doc,
              'ul',
              { class: 'tb-list', role: 'list' },
              items.map((item) =>
                h(doc, 'li', {}, [renderItem(item, view.readonly === true)])
              )
            )
          ])
        )
    )

  const renderItem = (item: MenuItem, overview: boolean): HTMLElement => {
    const keys = (overview ? item.bindings : item.bindings.slice(0, 1)).map(
      (binding) => h(doc, 'kbd', { class: 'tb-key' }, [displayBinding(binding)])
    )
    const content = [
      h(doc, 'span', { class: 'tb-item__keys' }, keys),
      h(doc, 'span', { class: 'tb-item__label' }, [item.label])
    ]
    if (overview) {
      return h(doc, 'div', { class: 'tb-item tb-item--static' }, content)
    }
    const button = h(
      doc,
      'button',
      { type: 'button', class: 'tb-item', 'data-tb-item': item.id },
      content
    )
    button.addEventListener('click', () => {
      item.select()
    })
    return button
  }

  const createPicker = (view: PickerView): PickerState => {
    const listId = `tb-picker-list-${String(counter)}`
    const input = h(doc, 'input', {
      type: 'text',
      class: 'tb-picker__input',
      role: 'combobox',
      autocomplete: 'off',
      'aria-expanded': 'true',
      'aria-controls': listId,
      'aria-label': view.title,
      placeholder: t('ui.search')
    })
    const list = h(doc, 'ul', {
      class: 'tb-picker__list',
      id: listId,
      role: 'listbox',
      'aria-multiselectable': view.multi ? 'true' : 'false'
    })
    const state: PickerState = {
      view,
      input,
      list,
      body: h(doc, 'div', { class: 'tb-modal__body tb-picker' }, [input, list]),
      results: [],
      active: -1,
      selected: new Map(),
      request: 0,
      timer: undefined
    }
    input.addEventListener('input', () => {
      clearTimeout(state.timer)
      state.timer = setTimeout(() => {
        search(state)
      }, SEARCH_DELAY_MS)
    })
    return state
  }

  const search = (state: PickerState): void => {
    state.request += 1
    const request = state.request
    state.view
      .source(state.input.value)
      .then((results) => {
        if (picker !== state || request !== state.request) {
          return
        }
        state.results = results
        state.active = results.length > 0 ? 0 : -1
        renderResults(state)
      })
      .catch(() => {
        if (picker === state && request === state.request) {
          state.results = []
          state.active = -1
          renderResults(state, 'ui.search_failed')
        }
      })
  }

  const renderResults = (
    state: PickerState,
    emptyKey = 'ui.no_results'
  ): void => {
    if (state.results.length === 0) {
      replaceChildren(state.list, [
        h(doc, 'li', { class: 'tb-picker__empty', role: 'presentation' }, [
          t(emptyKey)
        ])
      ])
      state.input.removeAttribute('aria-activedescendant')
      return
    }
    replaceChildren(
      state.list,
      state.results.map((item, index) => {
        const id = `${state.list.id}-${String(index)}`
        const option = h(
          doc,
          'li',
          {
            id,
            class:
              index === state.active
                ? 'tb-option tb-option--active'
                : 'tb-option',
            role: 'option',
            'aria-selected':
              state.view.multi && state.selected.has(item.id) ? 'true' : 'false'
          },
          [
            h(doc, 'span', { class: 'tb-option__label' }, [item.label]),
            item.detail === undefined
              ? null
              : h(doc, 'span', { class: 'tb-option__detail' }, [item.detail])
          ]
        )
        option.addEventListener('mousedown', (event) => {
          event.preventDefault()
          state.active = index
          choose(state, false)
        })
        return option
      })
    )
    const activeId = `${state.list.id}-${String(state.active)}`
    state.input.setAttribute('aria-activedescendant', activeId)
  }

  const choose = (state: PickerState, confirm: boolean): void => {
    const item = state.results[state.active]
    if (!state.view.multi) {
      if (item !== undefined) {
        state.view.submit([item])
      }
      return
    }
    if (confirm) {
      state.view.submit([...state.selected.values()])
      return
    }
    if (item !== undefined) {
      if (state.selected.has(item.id)) {
        state.selected.delete(item.id)
      } else {
        state.selected.set(item.id, item)
      }
      renderResults(state)
    }
  }

  const renderFooter = (view: View): HTMLElement => {
    const hints: [string, string][] =
      view.type === 'picker'
        ? view.multi
          ? [
              ['Enter', 'ui.hint.toggle'],
              ['Ctrl+Enter', 'ui.hint.confirm']
            ]
          : [['Enter', 'ui.hint.select']]
        : stack.length === 1
          ? [['?', 'ui.hint.help']]
          : []
    hints.push(['Esc', stack.length > 1 ? 'ui.hint.back' : 'ui.hint.close'])
    return h(
      doc,
      'div',
      { class: 'tb-modal__footer' },
      hints.map(([key, label]) =>
        h(doc, 'span', { class: 'tb-hint' }, [
          h(doc, 'kbd', { class: 'tb-key' }, [key]),
          ' ',
          t(label)
        ])
      )
    )
  }

  const back = (): void => {
    stack.pop()
    if (stack.length === 0) {
      close()
    } else {
      render()
    }
  }

  const trapFocus = (event: KeyboardEvent): void => {
    if (panel === null) {
      return
    }
    const focusable = [...panel.querySelectorAll<HTMLElement>(FOCUSABLE)]
    if (focusable.length === 0) {
      panel.focus()
      return
    }
    const index = focusable.indexOf(doc.activeElement as HTMLElement)
    const step = event.shiftKey ? -1 : 1
    const next =
      index < 0
        ? event.shiftKey
          ? focusable.length - 1
          : 0
        : (index + step + focusable.length) % focusable.length
    focusable[next]?.focus()
  }

  const moveActive = (state: PickerState, step: number): void => {
    if (state.results.length === 0) {
      return
    }
    state.active =
      (state.active + step + state.results.length) % state.results.length
    renderResults(state)
  }

  const handleKey = (event: KeyboardEvent): boolean => {
    const view = stack[stack.length - 1]
    if (view === undefined) {
      return false
    }
    if (event.key === 'Escape') {
      back()
      return true
    }
    if (event.key === 'Tab') {
      trapFocus(event)
      return true
    }
    if (picker !== null) {
      return handlePickerKey(picker, event)
    }
    if (event.key === 'Backspace') {
      back()
      return true
    }
    if (view.type === 'menu' && view.readonly !== true) {
      for (const group of view.groups) {
        const item = group.items.find((entry) =>
          entry.bindings.some((binding) => matchesBinding(binding, event))
        )
        if (item !== undefined) {
          item.select()
          return true
        }
      }
    }
    return false
  }

  const handlePickerKey = (
    state: PickerState,
    event: KeyboardEvent
  ): boolean => {
    switch (event.key) {
      case 'ArrowDown':
        moveActive(state, 1)
        return true
      case 'ArrowUp':
        moveActive(state, -1)
        return true
      case 'Enter':
        choose(state, event.ctrlKey || event.metaKey)
        return true
      case 'Backspace':
        if (state.input.value === '' && stack.length > 1) {
          back()
          return true
        }
        return false
      default:
        return false
    }
  }

  return {
    open(view) {
      stack.length = 0
      stack.push(view)
      render()
    },
    push(view) {
      stack.push(view)
      render()
    },
    close,
    isOpen,
    handleKey
  }
}
