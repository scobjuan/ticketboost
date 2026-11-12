import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { attachSubmitShortcut, submittableForm } from '../../src/editor/submit'
import { dispatchKey } from '../support/keys'

function timelineForm(action: string, buttons: string[] = ['add']) {
  const form = document.createElement('form')
  form.setAttribute('action', action)
  const select = document.createElement('select')
  const textarea = document.createElement('textarea')
  form.append(select, textarea)
  for (const name of buttons) {
    const button = document.createElement('button')
    button.type = 'submit'
    button.name = name
    form.append(button)
  }
  document.body.append(form)
  return { form, select, textarea }
}

describe('submittableForm', () => {
  beforeEach(() => {
    document.body.replaceChildren()
  })

  it('finds followup and task forms and their single submit button', () => {
    const followup = timelineForm('/front/itilfollowup.form.php')
    const task = timelineForm('/glpi/front/tickettask.form.php?x=1', ['update'])

    expect(submittableForm(followup.select)?.form).toBe(followup.form)
    expect(submittableForm(task.select)?.button.name).toBe('update')
  })

  it('leaves textareas to GLPI, which already handles them', () => {
    const { textarea } = timelineForm('/front/itilfollowup.form.php')

    expect(submittableForm(textarea)).toBeNull()
  })

  it('ignores other forms, ambiguous buttons and non-elements', () => {
    const other = timelineForm('/front/ticket.form.php')
    const twoButtons = timelineForm('/front/itilfollowup.form.php', [
      'add',
      'update'
    ])
    const noButton = timelineForm('/front/itilfollowup.form.php', [])
    const noAction = document.createElement('form')
    const input = document.createElement('input')
    noAction.append(input)
    const loose = document.createElement('input')

    expect(submittableForm(other.select)).toBeNull()
    expect(submittableForm(twoButtons.select)).toBeNull()
    expect(submittableForm(noButton.select)).toBeNull()
    expect(submittableForm(input)).toBeNull()
    expect(submittableForm(loose)).toBeNull()
    expect(submittableForm(null)).toBeNull()
    expect(submittableForm(window)).toBeNull()
  })
})

describe('attachSubmitShortcut', () => {
  let detach: () => void

  beforeEach(() => {
    document.body.replaceChildren()
    detach = attachSubmitShortcut(document)
  })

  afterEach(() => {
    detach()
    Reflect.deleteProperty(window, 'tinymce')
  })

  it('sends the form through its button on Ctrl+Enter and Cmd+Enter', () => {
    const { form, select } = timelineForm('/front/itilfollowup.form.php')
    const submit = vi
      .spyOn(form, 'requestSubmit')
      .mockImplementation(() => undefined)
    const triggerSave = vi.fn()
    Object.assign(window, {
      tinymce: { get: () => [], on: () => undefined, triggerSave }
    })

    const event = dispatchKey(select, 'keydown', {
      key: 'Enter',
      ctrlKey: true
    })
    dispatchKey(select, 'keydown', { key: 'Enter', metaKey: true })

    expect(event.defaultPrevented).toBe(true)
    expect(submit).toHaveBeenCalledTimes(2)
    expect(submit).toHaveBeenCalledWith(form.querySelector('button'))
    expect(triggerSave).toHaveBeenCalledTimes(2)
  })

  it('works without TinyMCE on the page', () => {
    const { form, select } = timelineForm('/front/tickettask.form.php')
    const submit = vi
      .spyOn(form, 'requestSubmit')
      .mockImplementation(() => undefined)

    dispatchKey(select, 'keydown', { key: 'Enter', ctrlKey: true })

    expect(submit).toHaveBeenCalledTimes(1)
  })

  it.each([
    { key: 'Enter' },
    { key: 'Enter', ctrlKey: true, metaKey: true },
    { key: 'Enter', ctrlKey: true, altKey: true },
    { key: 'Enter', ctrlKey: true, shiftKey: true },
    { key: 'Enter', ctrlKey: true, isComposing: true },
    { key: 'a', ctrlKey: true }
  ])('ignores %o', (init) => {
    const { form, select } = timelineForm('/front/itilfollowup.form.php')
    const submit = vi
      .spyOn(form, 'requestSubmit')
      .mockImplementation(() => undefined)

    const event = dispatchKey(select, 'keydown', init)

    expect(submit).not.toHaveBeenCalled()
    expect(event.defaultPrevented).toBe(false)
  })

  it('stops listening once detached', () => {
    const { form, select } = timelineForm('/front/itilfollowup.form.php')
    const submit = vi
      .spyOn(form, 'requestSubmit')
      .mockImplementation(() => undefined)

    detach()
    dispatchKey(select, 'keydown', { key: 'Enter', ctrlKey: true })

    expect(submit).not.toHaveBeenCalled()
  })
})
