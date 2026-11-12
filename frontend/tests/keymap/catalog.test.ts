import { describe, expect, it } from 'vitest'

import {
  ACTION_IDS,
  CATALOG,
  defaultKeymap,
  findAction,
  mergeKeymap
} from '../../src/keymap/catalog'
import { validateKeymap } from '../../src/keymap/validator'

describe('CATALOG', () => {
  it('describes every action exactly once', () => {
    expect(CATALOG.map((action) => action.id)).toEqual([...ACTION_IDS])
  })

  it('ships a default keymap without issues', () => {
    expect(validateKeymap(defaultKeymap())).toEqual([])
  })

  it('uses the documented default keys', () => {
    const keymap = defaultKeymap()

    expect(keymap.next).toEqual(['KeyN', 'ArrowRight'])
    expect(keymap.previous).toEqual(['KeyP', 'ArrowLeft'])
    expect(keymap.help).toEqual(['?'])
  })
})

describe('defaultKeymap', () => {
  it('returns a fresh copy each time', () => {
    const first = defaultKeymap()

    expect(first.next).not.toBe(defaultKeymap().next)
    expect(first.next).not.toBe(findAction('next')?.defaultKeys)
  })
})

describe('mergeKeymap', () => {
  it('replaces the bindings of overridden actions only', () => {
    const merged = mergeKeymap(defaultKeymap(), { next: ['KeyJ'], close: [] })

    expect(merged.next).toEqual(['KeyJ'])
    expect(merged.close).toEqual([])
    expect(merged.list).toEqual(['KeyL'])
  })
})

describe('findAction', () => {
  it('returns undefined for unknown ids', () => {
    expect(findAction('format_disk')).toBeUndefined()
    expect(findAction('close')?.group).toBe('ticket')
  })
})
