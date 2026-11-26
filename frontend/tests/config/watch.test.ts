import { afterEach, describe, expect, it, vi } from 'vitest'

import type { ConfigFormHandle } from '../../src/config/form'
import { watchConfigForms } from '../../src/config/watch'

const MESSAGES = JSON.stringify({
  recording: 'Press the new shortcut',
  invalid: '%s is not a valid shortcut',
  reserved: '%s is reserved',
  conflict: '%1$s is already used by %2$s',
  actions: {}
}).replaceAll('"', '&quot;')

const FORM = `<form data-tb-config data-tb-messages="${MESSAGES}">
  <div data-tb-action="next">
    <input data-tb-binding value="">
    <button type="button" data-tb-record>Record</button>
    <div data-tb-feedback></div>
  </div>
</form>`

const flush = (): Promise<void> =>
  new Promise((resolve) => {
    setTimeout(resolve, 0)
  })
const press = (): KeyboardEvent => {
  const event = new KeyboardEvent('keydown', {
    key: 'j',
    code: 'KeyJ',
    bubbles: true,
    cancelable: true
  })
  document.body.dispatchEvent(event)
  return event
}
const record = (): void => {
  document.querySelector<HTMLElement>('[data-tb-record]')!.click()
}
const value = (): string =>
  document.querySelector<HTMLInputElement>('[data-tb-binding]')!.value

let watching: ConfigFormHandle | null = null

afterEach(() => {
  watching?.destroy()
  watching = null
  document.body.innerHTML = ''
  vi.restoreAllMocks()
})

describe('watchConfigForms', () => {
  it('starts the forms already on the page', () => {
    document.body.innerHTML = FORM
    watching = watchConfigForms(document)

    record()
    press()

    expect(value()).toBe('KeyJ')
  })

  it('starts a form that arrives later, once', async () => {
    watching = watchConfigForms(document)
    const listen = vi.spyOn(document, 'addEventListener')
    document.body.innerHTML = FORM
    await flush()
    document.body.append(document.createElement('span'))
    await flush()

    record()
    press()

    expect(value()).toBe('KeyJ')
    expect(listen).toHaveBeenCalledTimes(1)
  })

  it('stops a form that leaves the page, even while it records', async () => {
    document.body.innerHTML = FORM
    watching = watchConfigForms(document)
    record()

    document.body.innerHTML = ''
    await flush()

    expect(press().defaultPrevented).toBe(false)
  })

  it('stops everything on destroy', async () => {
    document.body.innerHTML = FORM
    watching = watchConfigForms(document)
    record()

    watching.destroy()
    expect(press().defaultPrevented).toBe(false)

    document.body.innerHTML = FORM
    await flush()
    record()
    expect(press().defaultPrevented).toBe(false)
  })
})
