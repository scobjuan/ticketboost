import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import type { Api, ApiEnvelope } from '../src/api/client'
import type { App } from '../src/app'
import {
  boot,
  BOOTSTRAP_ENDPOINT,
  PANEL_ATTRIBUTE,
  READY_ATTRIBUTE,
  startPanel
} from '../src/boot'
import { defaultKeymap } from '../src/keymap/catalog'
import { DEFAULT_LEADER } from '../src/keys/leader'
import { dispatchKey, tapControl } from './support/keys'
import { setPageUrl } from './support/page'

const payload = {
  enabled: true,
  leader: { ...DEFAULT_LEADER },
  keymap: defaultKeymap(),
  available: ['my_tickets', 'next', 'help'],
  labels: {
    'ui.title': 'TicketBoost',
    'action.my_tickets': 'My tickets',
    'action.next': 'Next ticket',
    'action.help': 'Show all shortcuts',
    'message.action_failed': 'The action failed'
  }
}

function fakeApi(envelope: Partial<ApiEnvelope> | Error) {
  const calls = vi.fn(() =>
    envelope instanceof Error
      ? Promise.reject(envelope)
      : Promise.resolve({ ok: true, message: '', data: null, ...envelope })
  )
  const api = { get: calls, post: vi.fn() } as unknown as Api
  return Object.assign(api, { calls })
}

const labels = () =>
  [...document.querySelectorAll('.tb-item__label')].map(
    (node) => node.textContent
  )

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

  it('asks the server for the bootstrap payload when it has an API client', () => {
    const doc = document.implementation.createHTMLDocument('page')
    const api = fakeApi({ data: { enabled: false } })

    expect(boot(doc, { api })).toBe(true)

    expect(api.calls).toHaveBeenCalledWith(BOOTSTRAP_ENDPOINT)
  })

  it.each([
    ['an enabled user', { data: payload }, 'ready'],
    ['a disabled user', { data: { enabled: false } }, 'off']
  ])('publishes the panel state for %s', async (_name, envelope, state) => {
    const doc = document.implementation.createHTMLDocument('page')

    boot(doc, { api: fakeApi(envelope) })

    await vi.waitFor(() => {
      expect(doc.documentElement.getAttribute(PANEL_ATTRIBUTE)).toBe(state)
    })
  })

  it('reports a failing bootstrap request and keeps the page working', async () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    const doc = document.implementation.createHTMLDocument('page')
    const api = fakeApi(new Error('offline'))

    expect(boot(doc, { api })).toBe(true)
    await vi.waitFor(() => {
      expect(spy).toHaveBeenCalledWith('[ticketboost]', expect.any(Error))
    })
    spy.mockRestore()
  })
})

describe('startPanel', () => {
  let app: App | null = null

  beforeEach(() => {
    document.body.replaceChildren()
    setPageUrl('http://localhost/')
  })

  afterEach(() => {
    app?.destroy()
    app = null
    setPageUrl('http://localhost/')
  })

  it.each([
    ['an error answer', { ok: false, message: 'Forbidden', data: null }],
    ['a disabled user', { data: { enabled: false } }],
    ['an invalid payload', { data: { enabled: true, leader: 'Control' } }]
  ])('starts nothing for %s', async (_name, envelope) => {
    app = await startPanel(document, fakeApi(envelope))

    expect(app).toBeNull()
    tapControl(document)
    expect(document.querySelector('.tb-modal')).toBeNull()
  })

  it('opens the panel with the actions that work anywhere outside a ticket', async () => {
    app = await startPanel(document, fakeApi({ data: payload }))

    tapControl(document)

    expect(labels()).toEqual(['My tickets'])
  })

  it('offers the ticket actions on the form of an existing ticket', async () => {
    setPageUrl('http://localhost/front/ticket.form.php?id=5')
    app = await startPanel(document, fakeApi({ data: payload }))

    tapControl(document)

    expect(labels()).toEqual(['Next ticket', 'My tickets'])
  })

  it('works in a document without a window', async () => {
    const doc = document.implementation.createHTMLDocument('detached')

    app = await startPanel(doc, fakeApi({ data: payload }))

    expect(app).not.toBeNull()
  })

  it('shows the error toast when an action has no implementation yet', async () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    app = await startPanel(document, fakeApi({ data: payload }))
    tapControl(document)

    dispatchKey(document.body, 'keydown', { key: 'm', code: 'KeyM' })
    await vi.waitFor(() => {
      expect(document.querySelector('.tb-toast--error')?.textContent).toBe(
        'The action failed'
      )
    })
    spy.mockRestore()
  })
})
