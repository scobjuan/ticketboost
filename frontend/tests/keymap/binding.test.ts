import { describe, expect, it } from 'vitest'

import {
  displayBinding,
  formatBinding,
  matchesBinding,
  normalizeBinding,
  parseBinding
} from '../../src/keymap/binding'
import { keyLike } from '../support/keys'

describe('parseBinding', () => {
  it('parses physical keys', () => {
    expect(parseBinding('KeyN')).toEqual({
      ctrl: false,
      alt: false,
      shift: false,
      meta: false,
      code: 'KeyN'
    })
  })

  it('parses modifiers in any order', () => {
    expect(parseBinding('Shift+Ctrl+Digit1')).toMatchObject({
      ctrl: true,
      shift: true,
      code: 'Digit1'
    })
  })

  it('parses characters and drops Shift for them', () => {
    expect(parseBinding('Shift+?')).toMatchObject({ shift: false, char: '?' })
    expect(parseBinding('+')).toMatchObject({ char: '+' })
    expect(parseBinding('Ctrl++')).toMatchObject({ ctrl: true, char: '+' })
  })

  it.each([
    '',
    'Ctrl+',
    'Hyper+KeyN',
    'Ctrl+Ctrl+KeyN',
    'KeyNN',
    'F13',
    ' ',
    'ab'
  ])('rejects %j', (text) => {
    expect(parseBinding(text)).toBeNull()
  })
})

describe('formatBinding and normalizeBinding', () => {
  it('writes modifiers in canonical order', () => {
    expect(normalizeBinding('Meta+Shift+Alt+Ctrl+KeyA')).toBe(
      'Ctrl+Alt+Shift+Meta+KeyA'
    )
  })

  it('returns null for invalid text', () => {
    expect(normalizeBinding('nope')).toBeNull()
  })

  it('round-trips characters', () => {
    const binding = parseBinding('?')

    expect(binding && formatBinding(binding)).toBe('?')
  })
})

describe('displayBinding', () => {
  it.each([
    ['KeyN', 'N'],
    ['Digit3', '3'],
    ['Numpad4', '4'],
    ['ArrowRight', '→'],
    ['ArrowLeft', '←'],
    ['Escape', 'Esc'],
    ['F5', 'F5'],
    ['Ctrl+Shift+KeyK', 'Ctrl+Shift+K'],
    ['?', '?']
  ])('shows %s as %s', (text, label) => {
    const binding = parseBinding(text)

    expect(binding && displayBinding(binding)).toBe(label)
  })
})

describe('matchesBinding', () => {
  const n = parseBinding('KeyN')!
  const question = parseBinding('?')!

  it('matches the physical key with exact modifiers', () => {
    expect(matchesBinding(n, keyLike('n', { code: 'KeyN' }))).toBe(true)
    expect(
      matchesBinding(n, keyLike('N', { code: 'KeyN', shiftKey: true }))
    ).toBe(false)
    expect(
      matchesBinding(n, keyLike('n', { code: 'KeyN', ctrlKey: true }))
    ).toBe(false)
    expect(matchesBinding(n, keyLike('b', { code: 'KeyB' }))).toBe(false)
  })

  it('matches characters whatever Shift state the layout needs', () => {
    expect(
      matchesBinding(question, keyLike('?', { code: 'Slash', shiftKey: true }))
    ).toBe(true)
    expect(
      matchesBinding(question, keyLike('?', { code: 'Minus', shiftKey: true }))
    ).toBe(true)
    expect(matchesBinding(question, keyLike('/', { code: 'Slash' }))).toBe(
      false
    )
    expect(
      matchesBinding(question, keyLike('?', { code: 'Slash', altKey: true }))
    ).toBe(false)
  })
})
