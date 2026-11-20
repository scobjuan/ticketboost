import { describe, expect, it } from 'vitest'

import { parseBootstrap } from '../src/bootstrap'
import { DEFAULT_LEADER } from '../src/keys/leader'

const valid = {
  enabled: true,
  leader: { ...DEFAULT_LEADER },
  keymap: { next: ['KeyN'], help: ['?'] },
  available: ['help', 'next'],
  labels: { 'ui.title': 'TicketBoost' }
}

describe('parseBootstrap', () => {
  it('accepts a valid payload', () => {
    expect(parseBootstrap(valid)).toEqual({
      leader: DEFAULT_LEADER,
      keymap: { next: ['KeyN'], help: ['?'] },
      available: ['help', 'next'],
      labels: { 'ui.title': 'TicketBoost' },
      closeMode: 'quick_solution',
      options: {}
    })
  })

  it('reads the submenu options and the close mode', () => {
    const config = parseBootstrap({
      ...valid,
      closeMode: 'direct_close',
      options: {
        change_status: [
          { value: '1', label: 'New' },
          { value: 2, label: 'Bad value' },
          { label: 'No value' },
          'junk'
        ],
        change_priority: 'not a list',
        unknown_action: [{ value: '1', label: 'x' }]
      }
    })

    expect(config?.closeMode).toBe('direct_close')
    expect(config?.options).toEqual({
      change_status: [{ value: '1', label: 'New' }]
    })
  })

  it.each([
    ['an unknown close mode', { closeMode: 'delete' }],
    ['a close mode that is not text', { closeMode: 3 }],
    ['options that are not an object', { options: [] }]
  ])('falls back to the normal close flow with %s', (_name, extra) => {
    const config = parseBootstrap({ ...valid, ...extra })

    expect(config?.closeMode).toBe('quick_solution')
    expect(config?.options).toEqual({})
  })

  it.each([
    null,
    undefined,
    'x',
    42,
    [],
    { enabled: false },
    { enabled: 'yes' }
  ])('returns null for %j', (data) => {
    expect(parseBootstrap(data)).toBeNull()
  })

  it.each([
    ['leader missing', { ...valid, leader: undefined }],
    ['leader not an object', { ...valid, leader: 'Control' }],
    [
      'unknown leader key',
      { ...valid, leader: { ...valid.leader, key: 'Enter' } }
    ],
    [
      'unknown leader mode',
      { ...valid, leader: { ...valid.leader, mode: 'chord' } }
    ],
    ['keymap missing', { ...valid, keymap: undefined }],
    ['keymap bindings not a list', { ...valid, keymap: { next: 'KeyN' } }],
    ['keymap bindings not strings', { ...valid, keymap: { next: [1] } }],
    ['labels missing', { ...valid, labels: [] }]
  ])('rejects the payload when %s', (_name, data) => {
    expect(parseBootstrap(data)).toBeNull()
  })

  it('falls back to the default timing for bad numbers', () => {
    const config = parseBootstrap({
      ...valid,
      leader: {
        key: 'Alt',
        mode: 'hold',
        taps: -1,
        tapMaxMs: 'fast',
        tapWindowMs: Number.POSITIVE_INFINITY,
        holdMs: 800
      }
    })

    expect(config?.leader).toEqual({
      ...DEFAULT_LEADER,
      key: 'Alt',
      mode: 'hold',
      holdMs: 800
    })
  })

  it('ignores unknown actions and non-string labels without failing', () => {
    const config = parseBootstrap({
      ...valid,
      keymap: { next: ['KeyN'], reboot: ['KeyB'] },
      available: ['help', 'reboot', 7],
      labels: { 'ui.title': 'TicketBoost', 'ui.bad': 3 }
    })

    expect(config?.keymap).toEqual({ next: ['KeyN'] })
    expect(config?.available).toEqual(['help'])
    expect(config?.labels).toEqual({ 'ui.title': 'TicketBoost' })
  })

  it('treats a missing or malformed available list as empty', () => {
    expect(
      parseBootstrap({ ...valid, available: undefined })?.available
    ).toEqual([])
    expect(parseBootstrap({ ...valid, available: 'help' })?.available).toEqual(
      []
    )
  })
})
