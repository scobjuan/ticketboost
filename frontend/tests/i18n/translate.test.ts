import { describe, expect, it } from 'vitest'

import { createTranslator } from '../../src/i18n/translate'

describe('createTranslator', () => {
  it('returns the label or falls back to the key', () => {
    const t = createTranslator({ 'action.next': 'Siguiente' })

    expect(t('action.next')).toBe('Siguiente')
    expect(t('action.missing')).toBe('action.missing')
  })
})
