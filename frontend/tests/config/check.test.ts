import { describe, expect, it } from 'vitest'

import {
  checkFields,
  type ConfigMessages,
  format,
  splitBindings
} from '../../src/config/check'

const messages: ConfigMessages = {
  recording: 'Press the new shortcut',
  invalid: '%s is not a valid shortcut',
  reserved: '%s is kept by the browser or GLPI and cannot be used',
  conflict: '%1$s is already used by %2$s',
  actions: { next: 'Next ticket', previous: 'Previous ticket' }
}

describe('format', () => {
  it('fills plain and numbered placeholders', () => {
    expect(format('%s and %s', 'a', 'b')).toBe('a and b')
    expect(format('%2$s before %1$s', 'a', 'b')).toBe('b before a')
  })

  it('leaves a missing value empty', () => {
    expect(format('x %s')).toBe('x ')
  })
})

describe('splitBindings', () => {
  it('splits on commas and spaces and drops empty parts', () => {
    expect(splitBindings(' Ctrl+KeyK,  KeyN ,')).toEqual(['Ctrl+KeyK', 'KeyN'])
    expect(splitBindings('')).toEqual([])
  })
})

describe('checkFields', () => {
  it('accepts empty fields and the defaults', () => {
    expect(
      checkFields({ next: '', previous: 'KeyP, ArrowLeft' }, messages)
    ).toEqual({})
  })

  it('accepts a free shortcut', () => {
    expect(checkFields({ next: 'Ctrl+Shift+KeyK' }, messages)).toEqual({})
  })

  it('reports a shortcut that is not valid', () => {
    expect(checkFields({ next: 'Hyper+KeyN' }, messages)).toEqual({
      next: ['Hyper+KeyN is not a valid shortcut']
    })
  })

  it('reports a reserved shortcut', () => {
    expect(checkFields({ next: 'Ctrl+KeyW' }, messages)).toEqual({
      next: ['Ctrl+KeyW is kept by the browser or GLPI and cannot be used']
    })
  })

  it('reports both actions of a conflict with their names', () => {
    expect(checkFields({ next: 'KeyP' }, messages)).toEqual({
      next: ['KeyP is already used by Previous ticket'],
      previous: ['KeyP is already used by Next ticket']
    })
  })

  it('falls back to the action id when it has no name', () => {
    expect(checkFields({ list: 'KeyM' }, { ...messages, actions: {} })).toEqual(
      {
        list: ['KeyM is already used by my_tickets'],
        my_tickets: ['KeyM is already used by list']
      }
    )
  })
})
