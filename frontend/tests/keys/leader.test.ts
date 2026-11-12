import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import type { LeaderConfig } from '../../src/keys/leader'
import { createLeader, DEFAULT_LEADER } from '../../src/keys/leader'
import { keyLike } from '../support/keys'

const ctrl = keyLike('Control', { ctrlKey: true })

function setup(config: Partial<LeaderConfig> = {}) {
  const onLeader = vi.fn()
  const leader = createLeader({ ...DEFAULT_LEADER, ...config }, onLeader)
  return { leader, onLeader }
}

describe('createLeader in tap mode', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('triggers on a single press and release', () => {
    const { leader, onLeader } = setup()

    leader.keydown(ctrl)
    vi.advanceTimersByTime(100)
    leader.keyup(ctrl)

    expect(onLeader).toHaveBeenCalledTimes(1)
  })

  it('does not trigger for a chord such as Ctrl+C', () => {
    const { leader, onLeader } = setup()

    leader.keydown(ctrl)
    leader.keydown(keyLike('c', { code: 'KeyC', ctrlKey: true }))
    leader.keyup(keyLike('c', { code: 'KeyC', ctrlKey: true }))
    leader.keyup(ctrl)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('does not trigger when the key is held longer than tapMaxMs', () => {
    const { leader, onLeader } = setup()

    leader.keydown(ctrl)
    vi.advanceTimersByTime(DEFAULT_LEADER.tapMaxMs + 1)
    leader.keyup(ctrl)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('ignores auto-repeat keydown events', () => {
    const { leader, onLeader } = setup()

    leader.keydown(ctrl)
    leader.keydown({ ...ctrl, repeat: true })
    leader.keyup(ctrl)

    expect(onLeader).toHaveBeenCalledTimes(1)
  })

  it('ignores a second keydown without keyup', () => {
    const { leader, onLeader } = setup({ taps: 2 })

    leader.keydown(ctrl)
    leader.keydown(ctrl)
    leader.keyup(ctrl)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('cancels while an IME composition is running', () => {
    const { leader, onLeader } = setup()

    leader.keydown(ctrl)
    leader.keydown(keyLike('Process', { isComposing: true }))
    leader.keyup(ctrl)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('ignores keyup of other keys and keyup without keydown', () => {
    const { leader, onLeader } = setup()

    leader.keyup(ctrl)
    leader.keydown(ctrl)
    leader.keyup(keyLike('Shift'))
    leader.keyup(ctrl)

    expect(onLeader).toHaveBeenCalledTimes(1)
  })

  it('needs every tap within the window when taps is above one', () => {
    const { leader, onLeader } = setup({ taps: 2 })

    leader.keydown(ctrl)
    leader.keyup(ctrl)
    expect(onLeader).not.toHaveBeenCalled()
    vi.advanceTimersByTime(DEFAULT_LEADER.tapWindowMs - 1)
    leader.keydown(ctrl)
    leader.keyup(ctrl)

    expect(onLeader).toHaveBeenCalledTimes(1)
  })

  it('starts over when the tap window expires', () => {
    const { leader, onLeader } = setup({ taps: 2 })

    leader.keydown(ctrl)
    leader.keyup(ctrl)
    vi.advanceTimersByTime(DEFAULT_LEADER.tapWindowMs + 1)
    leader.keydown(ctrl)
    leader.keyup(ctrl)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('reset cancels a pending sequence', () => {
    const { leader, onLeader } = setup({ taps: 2 })

    leader.keydown(ctrl)
    leader.keyup(ctrl)
    leader.reset()
    leader.keydown(ctrl)
    leader.keyup(ctrl)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('uses the configured leader key', () => {
    const { leader, onLeader } = setup({ key: 'Alt' })

    leader.keydown(ctrl)
    leader.keyup(ctrl)
    leader.keydown(keyLike('Alt', { altKey: true }))
    leader.keyup(keyLike('Alt'))

    expect(onLeader).toHaveBeenCalledTimes(1)
  })
})

describe('createLeader in hold mode', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('triggers once the key has been held for holdMs', () => {
    const { leader, onLeader } = setup({ mode: 'hold' })

    leader.keydown(ctrl)
    vi.advanceTimersByTime(DEFAULT_LEADER.holdMs)

    expect(onLeader).toHaveBeenCalledTimes(1)
    leader.keyup(ctrl)
    expect(onLeader).toHaveBeenCalledTimes(1)
  })

  it('does not trigger when released early', () => {
    const { leader, onLeader } = setup({ mode: 'hold' })

    leader.keydown(ctrl)
    vi.advanceTimersByTime(DEFAULT_LEADER.holdMs - 1)
    leader.keyup(ctrl)
    vi.advanceTimersByTime(DEFAULT_LEADER.holdMs)

    expect(onLeader).not.toHaveBeenCalled()
  })

  it('does not trigger when another key is pressed while holding', () => {
    const { leader, onLeader } = setup({ mode: 'hold' })

    leader.keydown(ctrl)
    leader.keydown(keyLike('v', { code: 'KeyV', ctrlKey: true }))
    vi.advanceTimersByTime(DEFAULT_LEADER.holdMs)

    expect(onLeader).not.toHaveBeenCalled()
  })
})
