import type { KeyLike } from '../../src/keys/leader'

/**
 * Builds a fake key event for pure state machine tests.
 * @param key The `KeyboardEvent.key` value.
 * @param extra Other fields to override.
 * @returns The fake event.
 */
export function keyLike(key: string, extra: Partial<KeyLike> = {}): KeyLike {
  return {
    key,
    code: '',
    repeat: false,
    isComposing: false,
    ctrlKey: false,
    altKey: false,
    shiftKey: false,
    metaKey: false,
    ...extra
  }
}

/**
 * Dispatches a real keyboard event on a target, the way a user key press would.
 * @param target Where the event is dispatched.
 * @param type `keydown` or `keyup`.
 * @param init Event fields.
 * @returns The dispatched event, to inspect `defaultPrevented`.
 */
export function dispatchKey(
  target: EventTarget,
  type: 'keydown' | 'keyup',
  init: KeyboardEventInit
): KeyboardEvent {
  const event = new KeyboardEvent(type, {
    bubbles: true,
    cancelable: true,
    ...init
  })
  target.dispatchEvent(event)
  return event
}

/**
 * Taps the Control key (press and release) on a target.
 * @param target Where the events are dispatched.
 */
export function tapControl(target: EventTarget): void {
  dispatchKey(target, 'keydown', {
    key: 'Control',
    code: 'ControlLeft',
    ctrlKey: true
  })
  dispatchKey(target, 'keyup', { key: 'Control', code: 'ControlLeft' })
}
