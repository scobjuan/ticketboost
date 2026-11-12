import { describe, expect, it, vi } from 'vitest'

import type { TinyEditor, TinyManager } from '../../src/editor/tinymce'
import { tinymceOf, watchEditors } from '../../src/editor/tinymce'

function fakeEditor(doc: Document | null, initialized: boolean) {
  const listeners: (() => void)[] = []
  const editor: TinyEditor = {
    initialized,
    getDoc: () => doc,
    on: (_event, callback) => {
      listeners.push(callback)
    }
  }
  return {
    editor,
    init: () => {
      listeners.forEach((listener) => {
        listener()
      })
    }
  }
}

function fakeManager(editors: TinyEditor[]) {
  const added: ((event: { editor: TinyEditor }) => void)[] = []
  const manager: TinyManager = {
    get: () => editors,
    on: (_event, callback) => {
      added.push(callback)
    }
  }
  return {
    manager,
    add: (editor: TinyEditor) => {
      added.forEach((listener) => {
        listener({ editor })
      })
    }
  }
}

function fakeWindow(tinymce?: unknown) {
  const target = new EventTarget()
  return Object.assign(target, { tinymce }) as unknown as Window
}

describe('tinymceOf', () => {
  it('accepts only objects that look like the TinyMCE manager', () => {
    const { manager } = fakeManager([])

    expect(tinymceOf(fakeWindow(manager))).toBe(manager)
    expect(tinymceOf(fakeWindow())).toBeUndefined()
    expect(tinymceOf(fakeWindow(null))).toBeUndefined()
    expect(tinymceOf(fakeWindow({ get: () => [] }))).toBeUndefined()
  })
})

describe('watchEditors', () => {
  it('attaches initialized, pending and future editors once each', () => {
    const docA = document.implementation.createHTMLDocument('a')
    const docB = document.implementation.createHTMLDocument('b')
    const docC = document.implementation.createHTMLDocument('c')
    const ready = fakeEditor(docA, true)
    const pending = fakeEditor(docB, false)
    const { manager, add } = fakeManager([ready.editor, pending.editor])
    const onDocument = vi.fn()

    watchEditors(fakeWindow(manager), onDocument)
    expect(onDocument).toHaveBeenCalledTimes(1)

    pending.init()
    pending.init()
    const future = fakeEditor(docC, true)
    add(future.editor)
    add(ready.editor)

    const docs = onDocument.mock.calls.map(([doc]) => doc as Document)
    expect(docs).toHaveLength(3)
    expect(docs[0]).toBe(docA)
    expect(docs[1]).toBe(docB)
    expect(docs[2]).toBe(docC)
  })

  it('skips editors without a document', () => {
    const empty = fakeEditor(null, true)
    const { manager } = fakeManager([empty.editor])
    const onDocument = vi.fn()

    watchEditors(fakeWindow(manager), onDocument)

    expect(onDocument).not.toHaveBeenCalled()
  })

  it('retries once the window has loaded when TinyMCE is missing', () => {
    const doc = document.implementation.createHTMLDocument('late')
    const win = fakeWindow()
    const onDocument = vi.fn()

    watchEditors(win, onDocument)
    const { manager } = fakeManager([fakeEditor(doc, true).editor])
    Object.assign(win, { tinymce: manager })
    win.dispatchEvent(new Event('load'))
    win.dispatchEvent(new Event('load'))

    expect(onDocument).toHaveBeenCalledTimes(1)
  })

  it('never throws when an editor misbehaves', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => undefined)
    const broken: TinyEditor = {
      initialized: true,
      getDoc: () => {
        throw new Error('detached')
      },
      on: () => undefined
    }
    const { manager } = fakeManager([broken])

    expect(() => {
      watchEditors(fakeWindow(manager), vi.fn())
    }).not.toThrow()
    expect(spy).toHaveBeenCalled()
    spy.mockRestore()
  })
})
