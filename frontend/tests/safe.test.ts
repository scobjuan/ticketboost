import { describe, expect, it, vi } from 'vitest'

import { report, safe } from '../src/core/safe'

describe('safe', () => {
  it('returns the function result', () => {
    expect(safe(() => 42, 0)).toBe(42)
  })

  it('returns the fallback and reports when the function throws', () => {
    const onError = vi.fn()
    const error = new Error('boom')

    const result = safe(
      () => {
        throw error
      },
      'fallback',
      onError
    )

    expect(result).toBe('fallback')
    expect(onError).toHaveBeenCalledWith(error)
  })
})

describe('report', () => {
  it('logs with the plugin prefix', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)

    report('oops')

    expect(spy).toHaveBeenCalledWith('[ticketboost]', 'oops')
    spy.mockRestore()
  })
})
