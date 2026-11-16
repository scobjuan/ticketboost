import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import {
  ACTION_ENDPOINT,
  createActionHandlers,
  NAVIGATE_ENDPOINT,
  pickerItems,
  sameOriginUrl,
  SEARCH_ENDPOINT
} from '../../src/actions/handlers'
import type { Api, ApiEnvelope } from '../../src/api/client'
import { setPageUrl } from '../support/page'

function fakeApi(envelope: Partial<ApiEnvelope> | Error) {
  const calls = vi.fn<(endpoint: string) => Promise<unknown>>(() =>
    envelope instanceof Error
      ? Promise.reject(envelope)
      : Promise.resolve({ ok: true, message: '', data: null, ...envelope })
  )
  const posts = vi.fn<(endpoint: string, body: unknown) => Promise<unknown>>(
    () => calls('')
  )
  const api = { get: calls, post: posts } as unknown as Api
  return Object.assign(api, { calls, posts })
}

describe('sameOriginUrl', () => {
  const location = { href: 'http://localhost/a/b', origin: 'http://localhost' }

  it.each([
    [
      '/front/ticket.form.php?id=3',
      'http://localhost/front/ticket.form.php?id=3'
    ],
    ['other.php', 'http://localhost/a/other.php'],
    ['http://localhost/x', 'http://localhost/x']
  ])('accepts %s', (target, expected) => {
    expect(sameOriginUrl(location, target)).toBe(expected)
  })

  it.each([
    'http://evil.example/x',
    '//evil.example/x',
    'javascript:alert(1)',
    'http://'
  ])('rejects %s', (target) => {
    expect(sameOriginUrl(location, target)).toBeNull()
  })
})

describe('createActionHandlers', () => {
  beforeEach(() => {
    document.body.replaceChildren()
    setPageUrl('http://localhost/front/ticket.form.php?id=7')
  })

  afterEach(() => {
    setPageUrl('http://localhost/')
  })

  it('asks for the next ticket and goes to the address the server gives', async () => {
    const api = fakeApi({ redirect: '/front/ticket.form.php?id=9' })
    const navigate = vi.fn()

    await createActionHandlers(document, api, navigate).run('next')

    expect(api.calls).toHaveBeenCalledWith(`${NAVIGATE_ENDPOINT}?to=next&id=7`)
    expect(navigate).toHaveBeenCalledWith(
      'http://localhost/front/ticket.form.php?id=9'
    )
  })

  it('asks for the previous ticket the same way', async () => {
    const api = fakeApi({ redirect: '/front/ticket.form.php?id=2' })

    await createActionHandlers(document, api, vi.fn()).run('previous')

    expect(api.calls).toHaveBeenCalledWith(
      `${NAVIGATE_ENDPOINT}?to=previous&id=7`
    )
  })

  it.each(['list', 'my_tickets'] as const)(
    'resolves %s without a ticket id',
    async (id) => {
      setPageUrl('http://localhost/front/central.php')
      const api = fakeApi({ redirect: '/front/ticket.php' })
      const navigate = vi.fn()

      await createActionHandlers(document, api, navigate).run(id)

      expect(api.calls).toHaveBeenCalledWith(`${NAVIGATE_ENDPOINT}?to=${id}`)
      expect(navigate).toHaveBeenCalledWith('http://localhost/front/ticket.php')
    }
  )

  it('shows the message of a refusal and does not navigate', async () => {
    const api = fakeApi({ ok: false, message: 'There is no other ticket' })
    const navigate = vi.fn()

    await createActionHandlers(document, api, navigate).run('next')

    expect(document.querySelector('.tb-toast--error')?.textContent).toBe(
      'There is no other ticket'
    )
    expect(navigate).not.toHaveBeenCalled()
  })

  it.each([
    ['an answer without an address', {}],
    ['an address on another site', { redirect: 'http://evil.example/' }],
    ['a refusal without a message', { ok: false, message: '' }]
  ])('rejects %s', async (_name, envelope) => {
    const navigate = vi.fn()

    await expect(
      createActionHandlers(document, fakeApi(envelope), navigate).run('next')
    ).rejects.toThrow('did not give an address')
    expect(navigate).not.toHaveBeenCalled()
  })

  it('needs a ticket on screen to step through tickets', async () => {
    setPageUrl('http://localhost/front/ticket.php')
    const api = fakeApi({ redirect: '/x' })

    await expect(
      createActionHandlers(document, api, vi.fn()).run('next')
    ).rejects.toThrow('needs a ticket on screen')
    expect(api.calls).not.toHaveBeenCalled()
  })

  it('does not know the actions of later phases yet', async () => {
    const api = fakeApi({})

    await expect(
      createActionHandlers(document, api, vi.fn()).run('change_status')
    ).rejects.toThrow('not available yet')
    expect(api.calls).not.toHaveBeenCalled()
    expect(api.posts).not.toHaveBeenCalled()
  })

  it.each([
    ['assign_me', undefined, { action: 'assign_me', id: 7 }],
    ['unassign_me', undefined, { action: 'unassign_me', id: 7 }],
    [
      'assign_other',
      'user:3',
      { action: 'assign_other', id: 7, value: 'user:3' }
    ],
    [
      'unassign_others',
      ['user:3', 'group:2'],
      { action: 'unassign_others', id: 7, value: ['user:3', 'group:2'] }
    ]
  ] as const)('posts %s and reloads the ticket', async (id, value, body) => {
    const api = fakeApi({ redirect: '/front/ticket.form.php?id=7' })
    const navigate = vi.fn()

    await createActionHandlers(document, api, navigate).run(id, value)

    expect(api.posts).toHaveBeenCalledWith(ACTION_ENDPOINT, body)
    expect(navigate).toHaveBeenCalledWith(
      'http://localhost/front/ticket.form.php?id=7'
    )
  })

  it('shows the refusal of an assignment and does not reload', async () => {
    const api = fakeApi({ ok: false, message: 'You are already assigned' })
    const navigate = vi.fn()

    await createActionHandlers(document, api, navigate).run('assign_me')

    expect(document.querySelector('.tb-toast--error')?.textContent).toBe(
      'You are already assigned'
    )
    expect(navigate).not.toHaveBeenCalled()
  })

  it('needs a ticket on screen to assign', async () => {
    setPageUrl('http://localhost/front/ticket.php')
    const api = fakeApi({ redirect: '/x' })

    await expect(
      createActionHandlers(document, api, vi.fn()).run('assign_me')
    ).rejects.toThrow('needs a ticket on screen')
    expect(api.posts).not.toHaveBeenCalled()
  })

  it('searches the people a picker offers', async () => {
    const api = fakeApi({
      data: [{ id: 'user:3', label: 'Ana', detail: 'User' }, { bad: true }]
    })

    const items = await createActionHandlers(document, api, vi.fn()).search?.(
      'assign_other',
      'an a'
    )

    expect(api.calls).toHaveBeenCalledWith(
      `${SEARCH_ENDPOINT}?action=assign_other&id=7&q=an+a`
    )
    expect(items).toEqual([{ id: 'user:3', label: 'Ana', detail: 'User' }])
  })

  it('fails the search when the server refuses it', async () => {
    const api = fakeApi({ ok: false, message: 'Not allowed' })

    await expect(
      createActionHandlers(document, api, vi.fn()).search?.(
        'unassign_others',
        ''
      )
    ).rejects.toThrow('Not allowed')
  })

  it('passes a network failure on to the caller', async () => {
    const api = fakeApi(new Error('offline'))

    await expect(
      createActionHandlers(document, api, vi.fn()).run('list')
    ).rejects.toThrow('offline')
  })

  it('opens the address in the page by default', async () => {
    const assign = vi.fn()
    const win = document.defaultView as Window
    vi.spyOn(win.location, 'assign').mockImplementation(assign)
    const api = fakeApi({ redirect: '/front/ticket.php' })

    await createActionHandlers(document, api).run('list')

    expect(assign).toHaveBeenCalledWith('http://localhost/front/ticket.php')
    vi.restoreAllMocks()
  })

  it('has nothing to open in a document without a window', async () => {
    const doc = document.implementation.createHTMLDocument('detached')
    const api = fakeApi({ redirect: '/front/ticket.php' })

    await expect(createActionHandlers(doc, api).run('list')).rejects.toThrow(
      'did not give an address'
    )
  })
})

describe('pickerItems', () => {
  it('keeps well-formed entries, with or without a detail', () => {
    expect(
      pickerItems([
        { id: 'user:1', label: 'Ana' },
        { id: 'group:2', label: 'Support', detail: 'Group' }
      ])
    ).toEqual([
      { id: 'user:1', label: 'Ana' },
      { id: 'group:2', label: 'Support', detail: 'Group' }
    ])
  })

  it.each([
    ['not a list', { id: 'user:1' }],
    ['null', null],
    ['entries of the wrong shape', [null, 'x', { id: 1, label: 'a' }, {}]]
  ])('returns nothing for %s', (_name, data) => {
    expect(pickerItems(data)).toEqual([])
  })
})
