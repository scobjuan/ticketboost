import { describe, expect, it, vi } from 'vitest'

import { boot, READY_ATTRIBUTE } from '../src/boot'
import { dispatchKey } from './support/keys'

describe('boot', () => {
  it('marks the document as ready', () => {
    expect(boot(document)).toBe(true)
    expect(document.documentElement.getAttribute(READY_ATTRIBUTE)).toBe('ready')
  })

  it('installs the Ctrl+Enter shortcut', () => {
    const doc = document.implementation.createHTMLDocument('page')
    const form = doc.createElement('form')
    form.setAttribute('action', '/front/itilfollowup.form.php')
    const select = doc.createElement('select')
    const button = doc.createElement('button')
    button.type = 'submit'
    button.name = 'add'
    form.append(select, button)
    doc.body.append(form)
    const submit = vi
      .spyOn(form, 'requestSubmit')
      .mockImplementation(() => undefined)

    boot(doc)
    dispatchKey(select, 'keydown', { key: 'Enter', ctrlKey: true })

    expect(submit).toHaveBeenCalledWith(button)
  })

  it('never throws when the document is unusable', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    const broken = {} as Document

    expect(boot(broken)).toBe(false)
    expect(spy).toHaveBeenCalled()
    spy.mockRestore()
  })
})
