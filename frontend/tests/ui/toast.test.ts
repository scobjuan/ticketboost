import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { showToast } from '../../src/ui/toast'

describe('showToast', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    document.body.replaceChildren()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows status toasts in one polite live region and removes them', () => {
    const first = showToast(document, 'Saved', 'success', 1000)
    const second = showToast(document, 'Info')

    expect(document.querySelectorAll('.tb-toasts')).toHaveLength(1)
    expect(
      document.querySelector('.tb-toasts')?.getAttribute('aria-live')
    ).toBe('polite')
    expect(first.getAttribute('role')).toBe('status')
    expect(first.className).toContain('tb-toast--success')
    expect(second.className).toContain('tb-toast--info')

    vi.advanceTimersByTime(1000)
    expect(first.isConnected).toBe(false)
    expect(second.isConnected).toBe(true)
    vi.advanceTimersByTime(3000)
    expect(second.isConnected).toBe(false)
  })

  it('announces errors as alerts', () => {
    expect(showToast(document, 'Failed', 'error').getAttribute('role')).toBe(
      'alert'
    )
  })
})
