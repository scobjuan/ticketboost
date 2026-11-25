import { describe, expect, it } from 'vitest'

import { recordBinding, type RecordedKey } from '../../src/config/recorder'

const press = (overrides: Partial<RecordedKey>): RecordedKey => ({
  key: '',
  code: '',
  ctrlKey: false,
  altKey: false,
  shiftKey: false,
  metaKey: false,
  ...overrides
})

describe('recordBinding', () => {
  it('waits while only a modifier is down', () => {
    for (const code of ['ControlLeft', 'ShiftRight', 'AltLeft', 'MetaRight']) {
      expect(recordBinding(press({ code, key: 'Control' }))).toBeNull()
    }
  })

  it('records a letter as its physical key', () => {
    expect(recordBinding(press({ key: 'k', code: 'KeyK' }))).toBe('KeyK')
  })

  it('records the modifiers in canonical order', () => {
    expect(
      recordBinding(
        press({
          key: 'K',
          code: 'KeyK',
          shiftKey: true,
          ctrlKey: true,
          altKey: true
        })
      )
    ).toBe('Ctrl+Alt+Shift+KeyK')
  })

  it('records a lone symbol as the typed character', () => {
    expect(
      recordBinding(press({ key: '?', code: 'Slash', shiftKey: true }))
    ).toBe('?')
  })

  it('records a symbol with Ctrl as the physical key', () => {
    expect(
      recordBinding(press({ key: ',', code: 'Comma', ctrlKey: true }))
    ).toBe('Ctrl+Comma')
  })

  it('records digits and arrows by their code', () => {
    expect(recordBinding(press({ key: '1', code: 'Digit1' }))).toBe('Digit1')
    expect(recordBinding(press({ key: 'ArrowUp', code: 'ArrowUp' }))).toBe(
      'ArrowUp'
    )
  })

  it('refuses a key that has no binding', () => {
    expect(
      recordBinding(press({ key: 'Dead', code: 'IntlBackslash' }))
    ).toBeNull()
  })
})
