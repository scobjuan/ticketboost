import type { Mock } from 'vitest'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import type { ActionHandlers, App, AppConfig } from '../src/app'
import { startApp as start } from '../src/app'
import { ACTION_IDS, defaultKeymap } from '../src/keymap/catalog'
import { DEFAULT_LEADER } from '../src/keys/leader'
import { dispatchKey, tapControl } from './support/keys'

function config(extra: Partial<AppConfig> = {}): AppConfig {
  return {
    leader: DEFAULT_LEADER,
    keymap: defaultKeymap(),
    available: [...ACTION_IDS],
    labels: {
      'ui.title': 'TicketBoost',
      'ui.all_shortcuts': 'All shortcuts',
      'group.navigate': 'Navigate',
      'action.next': 'Next ticket',
      'action.change_status': 'Change status',
      'message.action_failed': 'The action failed'
    },
    options: {
      change_status: [
        { value: '1', label: 'New' },
        { value: '2', label: 'Processing (assigned)' }
      ]
    },
    ...extra
  }
}

const press = (init: KeyboardEventInit) =>
  dispatchKey(document.activeElement ?? document.body, 'keydown', init)
const isOpen = () => document.querySelector('.tb-modal') !== null
const title = () => document.querySelector('.tb-modal__title')?.textContent
const labels = () =>
  [...document.querySelectorAll('.tb-item__label')].map(
    (node) => node.textContent
  )

const running: App[] = []

function startApp(...args: Parameters<typeof start>): App {
  const app = start(...args)
  running.push(app)
  return app
}

describe('startApp', () => {
  let run: Mock
  let handlers: ActionHandlers
  let detach: (() => void) | undefined

  beforeEach(() => {
    vi.useFakeTimers()
    document.body.replaceChildren()
    run = vi.fn()
    handlers = { run }
  })

  afterEach(() => {
    detach?.()
    detach = undefined
    running.splice(0).forEach((app) => {
      app.destroy()
    })
    document.querySelector('.tb-overlay')?.remove()
    Reflect.deleteProperty(window, 'tinymce')
    vi.useRealTimers()
  })

  it('opens the panel on a leader tap and lists the available actions', () => {
    const app = startApp(
      document,
      config({ available: ['next', 'list', 'help'] }),
      handlers
    )

    tapControl(document)

    expect(isOpen()).toBe(true)
    expect(title()).toBe('TicketBoost')
    expect(labels()).toEqual(['Next ticket', 'action.list'])
    app.close()
    expect(isOpen()).toBe(false)
  })

  it('never opens on a chord such as Ctrl+C', () => {
    startApp(document, config(), handlers)

    dispatchKey(document.body, 'keydown', { key: 'Control', ctrlKey: true })
    dispatchKey(document.body, 'keydown', {
      key: 'c',
      code: 'KeyC',
      ctrlKey: true
    })
    dispatchKey(document.body, 'keyup', { key: 'Control' })

    expect(isOpen()).toBe(false)
  })

  it.each([
    ['mousedown', () => new MouseEvent('mousedown', { bubbles: true })],
    ['wheel', () => new WheelEvent('wheel', { bubbles: true })]
  ])('cancels the leader on %s (Ctrl+click, Ctrl+wheel)', (_name, make) => {
    startApp(document, config(), handlers)

    dispatchKey(document.body, 'keydown', { key: 'Control', ctrlKey: true })
    document.body.dispatchEvent(make())
    dispatchKey(document.body, 'keyup', { key: 'Control' })

    expect(isOpen()).toBe(false)
  })

  it('cancels the leader when the window loses focus', () => {
    startApp(document, config(), handlers)

    dispatchKey(document.body, 'keydown', { key: 'Control', ctrlKey: true })
    window.dispatchEvent(new Event('blur'))
    dispatchKey(document.body, 'keyup', { key: 'Control' })

    expect(isOpen()).toBe(false)
  })

  it('runs an action from its key, closes the panel and stops the event', async () => {
    startApp(document, config(), handlers)
    tapControl(document)

    const event = press({ key: 'n', code: 'KeyN' })
    await vi.advanceTimersByTimeAsync(0)

    expect(event.defaultPrevented).toBe(true)
    expect(run).toHaveBeenCalledWith('next', undefined)
    expect(isOpen()).toBe(false)
  })

  it('lets unbound keys through and ignores keyup while open', () => {
    startApp(document, config(), handlers)
    tapControl(document)

    const event = press({ key: 'z', code: 'KeyZ' })
    dispatchKey(document.body, 'keyup', { key: 'Control' })

    expect(event.defaultPrevented).toBe(false)
    expect(isOpen()).toBe(true)
  })

  it('opens submenus whose options are chosen with digits', async () => {
    startApp(document, config(), handlers)
    tapControl(document)

    press({ key: 's', code: 'KeyS' })
    expect(title()).toBe('TicketBoost / Change status')
    expect(labels()).toEqual(['New', 'Processing (assigned)'])
    press({ key: '2', code: 'Digit2' })
    await vi.advanceTimersByTimeAsync(0)

    expect(run).toHaveBeenCalledWith('change_status', '2')
  })

  it('accepts numpad digits and shows an empty submenu without options', async () => {
    startApp(
      document,
      config({
        options: {
          change_priority: Array.from({ length: 12 }, (_, index) => ({
            value: String(index + 1),
            label: `P${String(index + 1)}`
          }))
        }
      }),
      handlers
    )
    tapControl(document)

    press({ key: 's', code: 'KeyS' })
    expect(labels()).toEqual([])
    press({ key: 'Escape' })
    press({ key: 'r', code: 'KeyR' })
    expect(labels()).toHaveLength(9)
    press({ key: '3', code: 'Numpad3' })
    await vi.advanceTimersByTimeAsync(0)

    expect(run).toHaveBeenCalledWith('change_priority', '3')
  })

  it('searches with the picker and runs with the chosen id', async () => {
    const search = vi.fn(() =>
      Promise.resolve([
        { id: '7', label: 'tech' },
        { id: '8', label: 'Helpdesk' }
      ])
    )
    handlers.search = search
    startApp(document, config(), handlers)
    tapControl(document)

    press({ key: 'o', code: 'KeyO' })
    await vi.advanceTimersByTimeAsync(0)
    expect(search).toHaveBeenCalledWith('assign_other', '')
    press({ key: 'Enter' })
    await vi.advanceTimersByTimeAsync(0)

    expect(run).toHaveBeenCalledWith('assign_other', '7')
  })

  it('runs multi pickers with every selected id and skips empty selections', async () => {
    const search = vi.fn(() =>
      Promise.resolve([
        { id: '1', label: 'tech1' },
        { id: '2', label: 'tech2' }
      ])
    )
    handlers.search = search
    startApp(document, config(), handlers)
    tapControl(document)
    press({ key: 'x', code: 'KeyX' })
    await vi.advanceTimersByTimeAsync(0)

    press({ key: 'Enter', ctrlKey: true })
    await vi.advanceTimersByTimeAsync(0)
    expect(run).not.toHaveBeenCalled()

    press({ key: 'Enter' })
    press({ key: 'ArrowDown' })
    press({ key: 'Enter' })
    press({ key: 'Enter', ctrlKey: true })
    await vi.advanceTimersByTimeAsync(0)

    expect(run).toHaveBeenCalledWith('unassign_others', ['1', '2'])
  })

  it('shows empty pickers when no search source exists', async () => {
    startApp(document, config(), handlers)
    tapControl(document)

    press({ key: 'o', code: 'KeyO' })
    await vi.advanceTimersByTimeAsync(0)

    expect(document.querySelector('.tb-picker__empty')).not.toBeNull()
  })

  it('shows every shortcut in the overview', () => {
    startApp(document, config(), handlers)
    tapControl(document)

    press({ key: '?', code: 'Slash', shiftKey: true })

    expect(title()).toBe('TicketBoost / All shortcuts')
    expect(document.querySelectorAll('.tb-item--static')).toHaveLength(
      ACTION_IDS.length
    )
  })

  it('hides actions without usable bindings', () => {
    startApp(
      document,
      config({ keymap: { ...defaultKeymap(), close: [], list: ['Escape'] } }),
      handlers
    )
    tapControl(document)

    expect(labels()).not.toContain('action.close')
    expect(labels()).not.toContain('action.list')
  })

  it('shows an error toast when an action fails', async () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    run.mockRejectedValue(new Error('denied'))
    const app = startApp(document, config(), handlers)

    app.open()
    press({ key: 'a', code: 'KeyA' })
    await vi.advanceTimersByTimeAsync(0)

    expect(document.querySelector('.tb-toast--error')?.textContent).toBe(
      'The action failed'
    )
    expect(spy).toHaveBeenCalled()
    spy.mockRestore()
  })

  it('listens inside other documents until detached', () => {
    const app = startApp(document, config(), handlers)
    const frame = document.implementation.createHTMLDocument('editor')

    detach = app.attach(frame)
    tapControl(frame)
    expect(isOpen()).toBe(true)
    app.close()

    detach()
    detach = undefined
    tapControl(frame)
    expect(isOpen()).toBe(false)
  })

  it('listens inside TinyMCE editors found on the page', () => {
    const frame = document.implementation.createHTMLDocument('editor')
    Object.assign(window, {
      tinymce: {
        get: () => [
          { initialized: true, getDoc: () => frame, on: () => undefined }
        ],
        on: () => undefined
      }
    })
    startApp(document, config(), handlers)

    tapControl(frame)

    expect(isOpen()).toBe(true)
  })

  it('never lets an error escape a key handler', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    startApp(document, config(), handlers)
    tapControl(document)
    vi.spyOn(Element.prototype, 'querySelectorAll').mockImplementation(() => {
      throw new Error('broken DOM')
    })

    expect(() => press({ key: 'Tab' })).not.toThrow()
    expect(spy).toHaveBeenCalled()
    vi.restoreAllMocks()
  })

  it('stops listening everywhere once destroyed', () => {
    const frame = document.implementation.createHTMLDocument('editor')
    const app = startApp(document, config(), handlers)
    app.attach(frame)
    app.open()

    app.destroy()
    tapControl(document)
    tapControl(frame)
    app.attach(frame)()
    tapControl(frame)

    expect(isOpen()).toBe(false)
  })

  it('works in a document without a window', () => {
    const doc = document.implementation.createHTMLDocument('detached')
    Object.defineProperty(doc, 'defaultView', { value: null })

    expect(() => startApp(doc, config(), handlers)).not.toThrow()
  })
})
