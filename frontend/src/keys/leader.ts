/** Keys that can act as the leader. Matched against `KeyboardEvent.key`. */
export type LeaderKey = 'Control' | 'Alt' | 'Shift' | 'Meta'

/** Leader settings; admin defaults with optional user overrides. */
export interface LeaderConfig {
  readonly key: LeaderKey
  /** `tap`: press and release; `hold`: keep the key down for `holdMs`. */
  readonly mode: 'tap' | 'hold'
  /** Taps needed to trigger in `tap` mode (1 to 3). */
  readonly taps: number
  /** Longest press, from keydown to keyup, that still counts as a tap. */
  readonly tapMaxMs: number
  /** Longest pause between two taps. */
  readonly tapWindowMs: number
  /** How long the key must be held in `hold` mode. */
  readonly holdMs: number
}

/** The subset of `KeyboardEvent` the leader needs; easy to fake in tests. */
export interface KeyLike {
  readonly key: string
  readonly code: string
  readonly repeat: boolean
  readonly isComposing: boolean
  readonly ctrlKey: boolean
  readonly altKey: boolean
  readonly shiftKey: boolean
  readonly metaKey: boolean
}

/** Leader detector fed with raw keyboard events. */
export interface LeaderMachine {
  keydown(event: KeyLike): void
  keyup(event: KeyLike): void
  /** Cancels any candidate (blur, mouse use, modal opened). */
  reset(): void
}

/** Defaults from PLAN.md section 3.1. */
export const DEFAULT_LEADER: LeaderConfig = {
  key: 'Control',
  mode: 'tap',
  taps: 1,
  tapMaxMs: 300,
  tapWindowMs: 400,
  holdMs: 500
}

type Timer = ReturnType<typeof setTimeout>

/**
 * Creates the leader state machine. A tap is a press and release of the leader key with
 * no other key in between, so chords such as Ctrl+C never trigger it.
 * @param config Leader settings.
 * @param onLeader Called when the leader sequence completes.
 * @returns The machine to feed with keydown and keyup events.
 */
export function createLeader(
  config: LeaderConfig,
  onLeader: () => void
): LeaderMachine {
  let pressed = false
  let taps = 0
  let pressTimer: Timer | undefined
  let windowTimer: Timer | undefined

  const clearTimers = (): void => {
    clearTimeout(pressTimer)
    clearTimeout(windowTimer)
    pressTimer = undefined
    windowTimer = undefined
  }

  const reset = (): void => {
    clearTimers()
    pressed = false
    taps = 0
  }

  const trigger = (): void => {
    reset()
    onLeader()
  }

  const keydown = (event: KeyLike): void => {
    if (event.isComposing) {
      reset()
      return
    }
    if (event.key !== config.key) {
      reset()
      return
    }
    if (event.repeat || pressed) {
      return
    }
    clearTimeout(windowTimer)
    pressed = true
    pressTimer =
      config.mode === 'hold'
        ? setTimeout(trigger, config.holdMs)
        : setTimeout(reset, config.tapMaxMs)
  }

  const keyup = (event: KeyLike): void => {
    if (event.key !== config.key || !pressed) {
      return
    }
    clearTimeout(pressTimer)
    pressed = false
    if (config.mode === 'hold') {
      reset()
      return
    }
    taps += 1
    if (taps >= config.taps) {
      trigger()
      return
    }
    windowTimer = setTimeout(reset, config.tapWindowMs)
  }

  return { keydown, keyup, reset }
}
