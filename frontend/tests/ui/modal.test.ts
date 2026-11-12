import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { createTranslator } from '../../src/i18n/translate'
import { parseBinding } from '../../src/keymap/binding'
import type {
  MenuItem,
  MenuView,
  PickerItem,
  PickerView
} from '../../src/ui/modal'
import { createModal, SEARCH_DELAY_MS } from '../../src/ui/modal'

const t = createTranslator({
  'ui.hint.help': 'all shortcuts',
  'ui.hint.close': 'close',
  'ui.hint.back': 'back',
  'ui.no_results': 'Nothing found'
})

function key(init: KeyboardEventInit): KeyboardEvent {
  return new KeyboardEvent('keydown', { cancelable: true, ...init })
}

function item(
  id: string,
  keys: string[],
  extra: Partial<MenuItem> = {}
): MenuItem {
  return {
    id,
    label: `Label ${id}`,
    bindings: keys.map((text) => parseBinding(text)!),
    select: vi.fn(),
    ...extra
  }
}

function menu(items: MenuItem[], extra: Partial<MenuView> = {}): MenuView {
  return {
    type: 'menu',
    title: 'TicketBoost',
    groups: [
      { label: 'Navigate', items },
      { label: 'Empty', items: [] }
    ],
    ...extra
  }
}

const panel = () => document.querySelector<HTMLElement>('.tb-modal')!
const title = () => document.querySelector('.tb-modal__title')?.textContent
const footer = () => document.querySelector('.tb-modal__footer')?.textContent

describe('createModal menus', () => {
  beforeEach(() => {
    document.body.replaceChildren()
  })

  it('renders an accessible dialog with groups and key badges', () => {
    const modal = createModal(document, t)

    modal.open(
      menu([
        item('next', ['KeyN', 'ArrowRight']),
        item('help', ['?'], { hidden: true })
      ])
    )

    expect(modal.isOpen()).toBe(true)
    expect(panel().getAttribute('role')).toBe('dialog')
    expect(panel().getAttribute('aria-modal')).toBe('true')
    const labelledBy = panel().getAttribute('aria-labelledby')!
    expect(document.getElementById(labelledBy)?.textContent).toBe('TicketBoost')
    expect(document.querySelectorAll('.tb-group')).toHaveLength(1)
    expect(document.querySelector('.tb-group__label')?.textContent).toBe(
      'Navigate'
    )
    expect(document.querySelectorAll('.tb-item')).toHaveLength(1)
    expect(document.querySelector('.tb-item .tb-key')?.textContent).toBe('N')
    expect(footer()).toContain('all shortcuts')
    expect(footer()).toContain('close')
    expect(document.activeElement).toBe(panel())
  })

  it('selects items by key, including hidden ones, and by click', () => {
    const modal = createModal(document, t)
    const next = item('next', ['KeyN', 'ArrowRight'])
    const help = item('help', ['?'], { hidden: true })
    modal.open(menu([next, help]))

    expect(
      modal.handleKey(key({ key: 'ArrowRight', code: 'ArrowRight' }))
    ).toBe(true)
    expect(
      modal.handleKey(key({ key: '?', code: 'Slash', shiftKey: true }))
    ).toBe(true)
    document.querySelector<HTMLButtonElement>('[data-tb-item="next"]')!.click()

    expect(next.select).toHaveBeenCalledTimes(2)
    expect(help.select).toHaveBeenCalledTimes(1)
    expect(modal.handleKey(key({ key: 'z', code: 'KeyZ' }))).toBe(false)
  })

  it('stacks submenus with a breadcrumb and goes back with Esc or Backspace', () => {
    const modal = createModal(document, t)
    modal.open(menu([item('next', ['KeyN'])]))
    modal.push({
      type: 'menu',
      title: 'Status',
      groups: [{ label: '', items: [item('2', ['Digit2'])] }]
    })

    expect(title()).toBe('TicketBoost / Status')
    expect(document.querySelector('.tb-group__label')).toBeNull()
    expect(footer()).toContain('back')
    expect(footer()).not.toContain('all shortcuts')

    expect(modal.handleKey(key({ key: 'Escape' }))).toBe(true)
    expect(title()).toBe('TicketBoost')

    modal.push(menu([], { title: 'Priority' }))
    expect(modal.handleKey(key({ key: 'Backspace' }))).toBe(true)
    expect(title()).toBe('TicketBoost')

    expect(modal.handleKey(key({ key: 'Escape' }))).toBe(true)
    expect(modal.isOpen()).toBe(false)
    expect(document.querySelector('.tb-overlay')).toBeNull()
  })

  it('closes with Backspace at the root and ignores keys when closed', () => {
    const modal = createModal(document, t)

    expect(modal.handleKey(key({ key: 'Escape' }))).toBe(false)
    modal.open(menu([item('next', ['KeyN'])]))
    modal.handleKey(key({ key: 'Backspace' }))

    expect(modal.isOpen()).toBe(false)
    modal.close()
    expect(modal.isOpen()).toBe(false)
  })

  it('lists every binding in overview mode without selecting', () => {
    const modal = createModal(document, t)
    const next = item('next', ['KeyN', 'ArrowRight'])
    const help = item('help', ['?'])

    modal.open(menu([next, help], { readonly: true }))

    expect(document.querySelectorAll('button.tb-item')).toHaveLength(0)
    expect(document.querySelectorAll('.tb-item--static')).toHaveLength(2)
    expect(
      [
        ...document
          .querySelectorAll('.tb-item--static')[0]!
          .querySelectorAll('.tb-key')
      ].map((badge) => badge.textContent)
    ).toEqual(['N', '→'])
    expect(modal.handleKey(key({ key: 'n', code: 'KeyN' }))).toBe(false)
    expect(next.select).not.toHaveBeenCalled()
  })

  it('restores focus to the previous element on close', () => {
    const before = document.createElement('input')
    document.body.append(before)
    before.focus()
    const modal = createModal(document, t)

    modal.open(menu([item('next', ['KeyN'])]))
    expect(document.activeElement).toBe(panel())
    modal.close()

    expect(document.activeElement).toBe(before)
  })

  it('skips focus restore when the previous element is gone', () => {
    const before = document.createElement('input')
    document.body.append(before)
    before.focus()
    const modal = createModal(document, t)
    modal.open(menu([item('next', ['KeyN'])]))
    before.remove()

    expect(() => {
      modal.close()
    }).not.toThrow()
  })

  it('closes on a click outside the panel only', () => {
    const modal = createModal(document, t)
    modal.open(menu([item('next', ['KeyN'])]))

    panel().dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
    expect(modal.isOpen()).toBe(true)
    document
      .querySelector('.tb-overlay')!
      .dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
    expect(modal.isOpen()).toBe(false)
  })

  it('traps Tab focus inside the panel', () => {
    const modal = createModal(document, t)
    modal.open(menu([item('a', ['KeyA']), item('b', ['KeyB'])]))
    const [first, second] = [
      ...document.querySelectorAll<HTMLButtonElement>('button.tb-item')
    ]

    expect(modal.handleKey(key({ key: 'Tab' }))).toBe(true)
    expect(document.activeElement).toBe(first)
    modal.handleKey(key({ key: 'Tab' }))
    expect(document.activeElement).toBe(second)
    modal.handleKey(key({ key: 'Tab' }))
    expect(document.activeElement).toBe(first)
    modal.handleKey(key({ key: 'Tab', shiftKey: true }))
    expect(document.activeElement).toBe(second)

    panel().focus()
    modal.handleKey(key({ key: 'Tab', shiftKey: true }))
    expect(document.activeElement).toBe(second)
  })

  it('keeps focus on the panel when nothing is focusable', () => {
    const modal = createModal(document, t)
    modal.open(menu([item('a', ['KeyA'])], { readonly: true }))

    modal.handleKey(key({ key: 'Tab' }))

    expect(document.activeElement).toBe(panel())
  })

  it('replaces the stack when opened again', () => {
    const modal = createModal(document, t)
    modal.open(menu([]))
    modal.push(menu([], { title: 'Nested' }))

    modal.open(menu([], { title: 'Fresh' }))

    expect(title()).toBe('Fresh')
    expect(document.querySelectorAll('.tb-overlay')).toHaveLength(1)
  })
})

describe('createModal pickers', () => {
  const people: PickerItem[] = [
    { id: '1', label: 'tech1', detail: 'Technician' },
    { id: '2', label: 'tech2' },
    { id: '3', label: 'glpi' }
  ]

  function picker(extra: Partial<PickerView> = {}): PickerView {
    return {
      type: 'picker',
      title: 'Assign',
      multi: false,
      source: vi.fn((query: string) =>
        Promise.resolve(people.filter((person) => person.label.includes(query)))
      ),
      submit: vi.fn(),
      ...extra
    }
  }

  const options = () => [
    ...document.querySelectorAll<HTMLElement>('.tb-option')
  ]
  const input = () =>
    document.querySelector<HTMLInputElement>('.tb-picker__input')!

  beforeEach(() => {
    vi.useFakeTimers()
    document.body.replaceChildren()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('loads results, highlights the first and submits it with Enter', async () => {
    const modal = createModal(document, t)
    const view = picker()

    modal.open(view)
    await vi.advanceTimersByTimeAsync(0)

    expect(view.source).toHaveBeenCalledWith('')
    expect(document.activeElement).toBe(input())
    expect(input().getAttribute('role')).toBe('combobox')
    expect(options()).toHaveLength(3)
    expect(options()[0]!.className).toContain('tb-option--active')
    expect(input().getAttribute('aria-activedescendant')).toBe(options()[0]!.id)
    expect(document.querySelector('.tb-option__detail')?.textContent).toBe(
      'Technician'
    )
    expect(footer()).toContain('ui.hint.select')

    expect(modal.handleKey(key({ key: 'Enter' }))).toBe(true)
    expect(view.submit).toHaveBeenCalledWith([people[0]])
  })

  it('moves the highlight with arrows and wraps around', async () => {
    const modal = createModal(document, t)
    const view = picker()
    modal.open(view)
    await vi.advanceTimersByTimeAsync(0)

    expect(modal.handleKey(key({ key: 'ArrowUp' }))).toBe(true)
    expect(options()[2]!.className).toContain('tb-option--active')
    modal.handleKey(key({ key: 'ArrowDown' }))
    modal.handleKey(key({ key: 'ArrowDown' }))
    modal.handleKey(key({ key: 'Enter' }))

    expect(view.submit).toHaveBeenCalledWith([people[1]])
  })

  it('searches after a short delay and ignores stale answers', async () => {
    let resolveSlow: (items: PickerItem[]) => void = () => undefined
    const source = vi
      .fn<(query: string) => Promise<readonly PickerItem[]>>()
      .mockImplementationOnce(() => Promise.resolve(people))
      .mockImplementationOnce(
        () =>
          new Promise((resolve) => {
            resolveSlow = resolve
          })
      )
      .mockImplementationOnce(() => Promise.resolve([people[2]!]))
    const modal = createModal(document, t)
    modal.open(picker({ source }))
    await vi.advanceTimersByTimeAsync(0)

    input().value = 'te'
    input().dispatchEvent(new Event('input'))
    input().value = 'tec'
    input().dispatchEvent(new Event('input'))
    await vi.advanceTimersByTimeAsync(SEARCH_DELAY_MS)
    expect(source).toHaveBeenLastCalledWith('tec')

    input().value = 'g'
    input().dispatchEvent(new Event('input'))
    await vi.advanceTimersByTimeAsync(SEARCH_DELAY_MS)
    resolveSlow(people)
    await vi.advanceTimersByTimeAsync(0)

    expect(source).toHaveBeenCalledTimes(3)
    expect(options().map((option) => option.textContent)).toEqual(['glpi'])
  })

  it('shows translated empty and failure states', async () => {
    const modal = createModal(document, t)
    const source = vi
      .fn<(query: string) => Promise<readonly PickerItem[]>>()
      .mockResolvedValueOnce([])
      .mockRejectedValueOnce(new Error('offline'))
    const view = picker({ source })
    modal.open(view)
    await vi.advanceTimersByTimeAsync(0)

    expect(document.querySelector('.tb-picker__empty')?.textContent).toBe(
      'Nothing found'
    )
    expect(input().hasAttribute('aria-activedescendant')).toBe(false)
    modal.handleKey(key({ key: 'ArrowDown' }))
    modal.handleKey(key({ key: 'Enter' }))
    expect(view.submit).not.toHaveBeenCalled()

    input().dispatchEvent(new Event('input'))
    await vi.advanceTimersByTimeAsync(SEARCH_DELAY_MS)
    expect(document.querySelector('.tb-picker__empty')?.textContent).toBe(
      'ui.search_failed'
    )
  })

  it('ignores answers that arrive after the picker closed', async () => {
    let resolveLate: (items: PickerItem[]) => void = () => undefined
    let rejectLate: (error: Error) => void = () => undefined
    const modal = createModal(document, t)
    modal.open(
      picker({
        source: () =>
          new Promise((resolve) => {
            resolveLate = resolve
          })
      })
    )
    modal.close()
    resolveLate(people)
    await vi.advanceTimersByTimeAsync(0)

    modal.open(
      picker({
        source: () =>
          new Promise((_, reject) => {
            rejectLate = reject
          })
      })
    )
    modal.push(menu([]))
    rejectLate(new Error('late'))
    await vi.advanceTimersByTimeAsync(0)

    expect(document.querySelector('.tb-picker')).toBeNull()
  })

  it('toggles items in multi mode and confirms with Ctrl+Enter', async () => {
    const modal = createModal(document, t)
    const view = picker({ multi: true })
    modal.open(view)
    await vi.advanceTimersByTimeAsync(0)

    expect(footer()).toContain('ui.hint.toggle')
    expect(footer()).toContain('ui.hint.confirm')
    expect(
      document
        .querySelector('.tb-picker__list')
        ?.getAttribute('aria-multiselectable')
    ).toBe('true')

    modal.handleKey(key({ key: 'Enter' }))
    expect(options()[0]!.getAttribute('aria-selected')).toBe('true')
    modal.handleKey(key({ key: 'ArrowDown' }))
    modal.handleKey(key({ key: 'Enter' }))
    modal.handleKey(key({ key: 'Enter' }))
    expect(options()[1]!.getAttribute('aria-selected')).toBe('false')
    modal.handleKey(key({ key: 'ArrowDown' }))
    options()[2]!.dispatchEvent(
      new MouseEvent('mousedown', { cancelable: true })
    )
    expect(view.submit).not.toHaveBeenCalled()

    modal.handleKey(key({ key: 'Enter', ctrlKey: true }))
    expect(view.submit).toHaveBeenCalledWith([people[0], people[2]])
  })

  it('chooses with the mouse in single mode', async () => {
    const modal = createModal(document, t)
    const view = picker()
    modal.open(view)
    await vi.advanceTimersByTimeAsync(0)

    options()[1]!.dispatchEvent(
      new MouseEvent('mousedown', { cancelable: true })
    )

    expect(view.submit).toHaveBeenCalledWith([people[1]])
  })

  it('goes back with Backspace only when the search is empty', async () => {
    const modal = createModal(document, t)
    modal.open(menu([]))
    modal.push(picker())
    await vi.advanceTimersByTimeAsync(0)

    input().value = 'x'
    expect(modal.handleKey(key({ key: 'Backspace' }))).toBe(false)
    expect(modal.handleKey(key({ key: 'a', code: 'KeyA' }))).toBe(false)
    input().value = ''
    expect(modal.handleKey(key({ key: 'Backspace' }))).toBe(true)
    expect(title()).toBe('TicketBoost')
  })

  it('keeps Backspace for the input when the picker is the root view', async () => {
    const modal = createModal(document, t)
    modal.open(picker())
    await vi.advanceTimersByTimeAsync(0)

    expect(modal.handleKey(key({ key: 'Backspace' }))).toBe(false)
    expect(modal.isOpen()).toBe(true)
  })
})
