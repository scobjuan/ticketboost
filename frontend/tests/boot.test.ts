import { describe, expect, it, vi } from 'vitest'

import { boot, READY_ATTRIBUTE } from '../src/boot'

describe('boot', () => {
  it('marks the document as ready', () => {
    expect(boot(document)).toBe(true)
    expect(document.documentElement.getAttribute(READY_ATTRIBUTE)).toBe('ready')
  })

  it('never throws when the document is unusable', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    const broken = {} as Document

    expect(boot(broken)).toBe(false)
    expect(spy).toHaveBeenCalled()
    spy.mockRestore()
  })
})
