import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

import { describe, expect, it } from 'vitest'

import { normalizeBinding } from '../../src/keymap/binding'
import { defaultKeymap } from '../../src/keymap/catalog'
import { RESERVED_BINDINGS, validateKeymap } from '../../src/keymap/validator'

interface SharedCases {
  defaults: Record<string, string[]>
  reserved: string[]
  normalize: [string, string][]
  invalid: string[]
  validate: {
    name: string
    keymap: Record<string, string[]>
    issues: unknown[]
  }[]
}

// The same file drives the PHP tests, so both validators implement one grammar (ADR 0017).
const cases = JSON.parse(
  readFileSync(resolve(process.cwd(), 'shared/keymap-cases.json'), 'utf8')
) as SharedCases

describe('shared keymap cases', () => {
  it('keeps the default keymap in sync with PHP', () => {
    expect(defaultKeymap()).toEqual(cases.defaults)
  })

  it('keeps the reserved bindings in sync with PHP', () => {
    expect([...RESERVED_BINDINGS]).toEqual(cases.reserved)
  })

  it.each(cases.normalize)('normalizes %j to %j', (input, expected) => {
    expect(normalizeBinding(input)).toBe(expected)
  })

  it.each(cases.invalid)('rejects the binding %j', (input) => {
    expect(normalizeBinding(input)).toBeNull()
  })

  it.each(cases.validate)('validates: $name', ({ keymap, issues }) => {
    expect(validateKeymap(keymap)).toEqual(issues)
  })
})
