import { afterEach, describe, expect, it } from 'vitest'

import type { ConfigFormHandle } from '../../src/config/form'
import { readMessages, startConfigForm } from '../../src/config/form'

const MESSAGES = {
  recording: 'Press the new shortcut',
  invalid: '%s is not a valid shortcut',
  reserved: '%s is reserved',
  conflict: '%1$s is already used by %2$s',
  actions: { next: 'Next ticket', previous: 'Previous ticket' }
}

function render(messages: unknown = MESSAGES): void {
  const json = JSON.stringify(messages).replaceAll('"', '&quot;')
  document.body.innerHTML = `
    <form data-tb-config data-tb-messages="${json}">
      ${['next', 'previous']
        .map(
          (id) => `<div data-tb-action="${id}">
            <input data-tb-binding name="keymap[${id}]" value="">
            <button type="button" data-tb-record>Record</button>
            <div data-tb-feedback></div>
          </div>`
        )
        .join('')}
    </form>`
}

const row = (id: string): HTMLElement =>
  document.querySelector<HTMLElement>(`[data-tb-action="${id}"]`)!
const input = (id: string): HTMLInputElement =>
  row(id).querySelector<HTMLInputElement>('[data-tb-binding]')!
const feedback = (id: string): string =>
  row(id).querySelector('[data-tb-feedback]')!.textContent
const press = (init: KeyboardEventInit): KeyboardEvent => {
  const event = new KeyboardEvent('keydown', {
    bubbles: true,
    cancelable: true,
    ...init
  })
  document.body.dispatchEvent(event)
  return event
}
const click = (id: string): void => {
  row(id).querySelector<HTMLElement>('[data-tb-record]')!.click()
}

let running: ConfigFormHandle | null = null
const start = (): ConfigFormHandle | null => {
  running = startConfigForm(document)
  return running
}

afterEach(() => {
  running?.destroy()
  running = null
  document.body.innerHTML = ''
})

describe('readMessages', () => {
  it('reads the texts of the page', () => {
    render()
    expect(readMessages(document.querySelector('form')!)).toEqual(MESSAGES)
  })

  it.each([
    ['not json', '{'],
    ['not an object', '3'],
    ['missing texts', '{"recording":"x"}']
  ])('returns null for %s', (_name, raw) => {
    const form = document.createElement('form')
    form.dataset.tbMessages = raw
    expect(readMessages(form)).toBeNull()
  })

  it('returns null without the attribute', () => {
    expect(readMessages(document.createElement('form'))).toBeNull()
  })
})

describe('startConfigForm', () => {
  it('does nothing without the form or without its texts', () => {
    expect(start()).toBeNull()
    document.body.innerHTML = '<form data-tb-config></form>'
    expect(start()).toBeNull()
  })

  it('checks a shortcut as it is typed', () => {
    render()
    expect(start()).not.toBeNull()

    input('next').value = 'KeyP'
    input('next').dispatchEvent(new Event('input'))

    expect(feedback('next')).toBe('KeyP is already used by Previous ticket')
    expect(feedback('previous')).toBe('KeyP is already used by Next ticket')
    expect(input('next').classList.contains('is-invalid')).toBe(true)

    input('next').value = 'Ctrl+Shift+KeyK'
    input('next').dispatchEvent(new Event('input'))
    expect(feedback('next')).toBe('')
    expect(input('next').classList.contains('is-invalid')).toBe(false)
  })

  it('records the next key pressed into the field', () => {
    render()
    start()

    click('next')
    expect(feedback('next')).toBe('Press the new shortcut')
    expect(
      row('next')
        .querySelector('[data-tb-record]')!
        .classList.contains('active')
    ).toBe(true)

    expect(
      press({ key: 'Control', code: 'ControlLeft', ctrlKey: true })
        .defaultPrevented
    ).toBe(true)
    expect(input('next').value).toBe('')

    press({ key: 'K', code: 'KeyK', ctrlKey: true, shiftKey: true })
    expect(input('next').value).toBe('Ctrl+Shift+KeyK')
    expect(feedback('next')).toBe('')
    expect(
      row('next')
        .querySelector('[data-tb-record]')!
        .classList.contains('active')
    ).toBe(false)
  })

  it('shows a conflict found by recording', () => {
    render()
    start()

    click('next')
    press({ key: 'p', code: 'KeyP' })

    expect(feedback('next')).toContain('already used by Previous ticket')
  })

  it('cancels the recording with Escape', () => {
    render()
    start()

    click('next')
    press({ key: 'Escape', code: 'Escape' })
    press({ key: 'k', code: 'KeyK' })

    expect(input('next').value).toBe('')
    expect(feedback('next')).toBe('')
  })

  it('records into the last field whose button was pressed', () => {
    render()
    start()

    click('next')
    click('previous')
    press({ key: 'k', code: 'KeyK' })

    expect(input('next').value).toBe('')
    expect(input('previous').value).toBe('KeyK')
  })

  it('keeps recording when the key has no binding', () => {
    render()
    start()

    click('next')
    press({ key: 'Dead', code: 'IntlBackslash' })
    expect(input('next').value).toBe('')
    expect(feedback('next')).toBe('Press the new shortcut')
  })

  it('stops recording and checking once destroyed', () => {
    render()
    const handle = start()
    click('next')
    handle?.destroy()

    expect(press({ key: 'k', code: 'KeyK' }).defaultPrevented).toBe(false)
    input('next').value = 'KeyP'
    input('next').dispatchEvent(new Event('input'))
    expect(feedback('next')).toBe('Press the new shortcut')
  })

  it('leaves keys alone when nothing is recording', () => {
    render()
    start()

    expect(press({ key: 'k', code: 'KeyK' }).defaultPrevented).toBe(false)
  })
})
