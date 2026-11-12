import { describe, expect, it } from 'vitest'

import {
  RESERVED_BINDINGS,
  usableBindings,
  validateKeymap
} from '../../src/keymap/validator'

describe('validateKeymap', () => {
  it('reports unknown actions', () => {
    expect(validateKeymap({ reboot: ['KeyB'] })).toEqual([
      { code: 'unknown_action', action: 'reboot' }
    ])
  })

  it('reports invalid bindings', () => {
    expect(validateKeymap({ next: ['Hyper+KeyN'] })).toEqual([
      { code: 'invalid_binding', action: 'next', binding: 'Hyper+KeyN' }
    ])
  })

  it.each(RESERVED_BINDINGS)('rejects the reserved binding %s', (binding) => {
    expect(validateKeymap({ next: [binding] })).toEqual([
      { code: 'reserved_binding', action: 'next', binding }
    ])
  })

  it('normalizes before checking reserved chords', () => {
    expect(validateKeymap({ next: ['Alt+Ctrl+KeyG'] })[0]?.code).toBe(
      'reserved_binding'
    )
  })

  it('reports both sides of a conflict', () => {
    expect(
      validateKeymap({ next: ['KeyN'], list: ['KeyL'], close: ['KeyN'] })
    ).toEqual([
      { code: 'conflict', action: 'next', binding: 'KeyN', with: ['close'] },
      { code: 'conflict', action: 'close', binding: 'KeyN', with: ['next'] }
    ])
  })

  it('reports a binding listed twice for the same action', () => {
    expect(validateKeymap({ next: ['KeyN', 'KeyN'] })).toEqual([
      { code: 'conflict', action: 'next', binding: 'KeyN', with: ['next'] }
    ])
  })

  it('treats a missing binding list as empty', () => {
    expect(validateKeymap({ next: undefined })).toEqual([])
  })
})

describe('usableBindings', () => {
  it('drops invalid and reserved bindings and keeps order', () => {
    const bindings = usableBindings(['Escape', 'KeyN', 'bogus', 'ArrowRight'])

    expect(bindings.map((binding) => binding.code)).toEqual([
      'KeyN',
      'ArrowRight'
    ])
    expect(usableBindings(undefined)).toEqual([])
  })
})
