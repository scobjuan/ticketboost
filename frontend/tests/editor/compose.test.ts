import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import {
  COMPOSER_BLOCKS,
  COMPOSER_SHOWN_MS,
  COMPOSER_TIMEOUT_MS,
  openComposer
} from '../../src/editor/compose'

function block(kind: keyof typeof COMPOSER_BLOCKS): HTMLElement {
  const element = document.createElement('div')
  element.id = COMPOSER_BLOCKS[kind]
  document.body.append(element)
  return element
}

function textarea(parent: HTMLElement, id = ''): HTMLTextAreaElement {
  const area = document.createElement('textarea')
  area.name = 'content'
  if (id !== '') {
    area.id = id
  }
  parent.append(area)
  return area
}

/** Fakes Bootstrap; `show` marks the block as open at once, like a finished animation. */
function bootstrap(show = vi.fn()) {
  Object.assign(window, {
    bootstrap: {
      Collapse: {
        getOrCreateInstance: (element: Element) => ({
          show: () => {
            show()
            element.classList.add('show')
          }
        })
      }
    }
  })
  return show
}

describe('openComposer', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    document.body.replaceChildren()
  })

  afterEach(() => {
    Reflect.deleteProperty(window, 'bootstrap')
    Reflect.deleteProperty(window, 'tinymce')
    vi.useRealTimers()
  })

  it('shows the form and focuses the plain text area', async () => {
    const show = bootstrap()
    const area = textarea(block('followup'))

    await expect(openComposer(document, 'followup')).resolves.toBe(true)

    expect(show).toHaveBeenCalledOnce()
    expect(document.activeElement).toBe(area)
  })

  it('focuses the TinyMCE editor when there is one', async () => {
    bootstrap()
    textarea(block('task'), 'content_12')
    const focus = vi.fn()
    Object.assign(window, { tinymce: { get: () => ({ focus }) } })

    await expect(openComposer(document, 'task')).resolves.toBe(true)

    expect(focus).toHaveBeenCalledOnce()
  })

  it('waits for the editor to initialize before focusing it', async () => {
    bootstrap()
    textarea(block('task'), 'content_12')
    const editor = { initialized: false, focus: vi.fn() }
    Object.assign(window, { tinymce: { get: () => editor } })
    const result = openComposer(document, 'task')

    await vi.advanceTimersByTimeAsync(200)
    expect(editor.focus).not.toHaveBeenCalled()
    editor.initialized = true
    await vi.advanceTimersByTimeAsync(100)

    await expect(result).resolves.toBe(true)
    expect(editor.focus).toHaveBeenCalledOnce()
  })

  it('falls back to the text area when the editor never starts', async () => {
    bootstrap()
    const area = textarea(block('task'), 'content_12')
    Object.assign(window, { tinymce: { get: () => null } })
    const result = openComposer(document, 'task')

    await vi.advanceTimersByTimeAsync(COMPOSER_TIMEOUT_MS + 100)

    await expect(result).resolves.toBe(true)
    expect(document.activeElement).toBe(area)
  })

  it('does not wait for TinyMCE on a text area without an id', async () => {
    bootstrap()
    const area = textarea(block('task'))
    Object.assign(window, { tinymce: { get: () => null } })

    await expect(openComposer(document, 'task')).resolves.toBe(true)

    expect(document.activeElement).toBe(area)
  })

  it('waits for the collapse animation to end before focusing', async () => {
    const parent = block('followup')
    const area = textarea(parent)
    Object.assign(window, {
      bootstrap: {
        Collapse: {
          getOrCreateInstance: () => ({
            show: () => {
              parent.classList.add('collapsing')
            }
          })
        }
      }
    })
    const result = openComposer(document, 'followup')

    await vi.advanceTimersByTimeAsync(100)
    expect(document.activeElement).not.toBe(area)
    parent.classList.replace('collapsing', 'show')
    parent.dispatchEvent(new Event('shown.bs.collapse'))
    await vi.advanceTimersByTimeAsync(0)

    await expect(result).resolves.toBe(true)
    expect(document.activeElement).toBe(area)
  })

  it('focuses anyway when the animation event never comes', async () => {
    const parent = block('followup')
    const area = textarea(parent)
    Object.assign(window, {
      bootstrap: {
        Collapse: {
          getOrCreateInstance: () => ({
            show: () => {
              parent.classList.add('collapsing')
            }
          })
        }
      }
    })
    const result = openComposer(document, 'followup')

    await vi.advanceTimersByTimeAsync(COMPOSER_SHOWN_MS + 100)

    await expect(result).resolves.toBe(true)
    expect(document.activeElement).toBe(area)
  })

  it('waits for a form that GLPI loads on first open', async () => {
    bootstrap()
    const parent = block('followup')
    const result = openComposer(document, 'followup')
    await vi.advanceTimersByTimeAsync(120)
    const area = textarea(parent)
    await vi.advanceTimersByTimeAsync(100)

    await expect(result).resolves.toBe(true)
    expect(document.activeElement).toBe(area)
  })

  it('gives up when the form never loads', async () => {
    bootstrap()
    block('followup')
    const result = openComposer(document, 'followup')

    await vi.advanceTimersByTimeAsync(COMPOSER_TIMEOUT_MS + 100)

    await expect(result).resolves.toBe(false)
  })

  it('waits for the block when the timeline is still being drawn', async () => {
    bootstrap()
    const result = openComposer(document, 'followup')
    await vi.advanceTimersByTimeAsync(120)
    const area = textarea(block('followup'))
    await vi.advanceTimersByTimeAsync(200)

    await expect(result).resolves.toBe(true)
    expect(document.activeElement).toBe(area)
  })

  it('returns false when the block never appears', async () => {
    bootstrap()
    const result = openComposer(document, 'followup')

    await vi.advanceTimersByTimeAsync(COMPOSER_TIMEOUT_MS + 100)

    await expect(result).resolves.toBe(false)
  })

  it('returns false when Bootstrap is missing', async () => {
    textarea(block('followup'))

    await expect(openComposer(document, 'followup')).resolves.toBe(false)
  })

  it('returns false in a document without a window', async () => {
    const detached = document.implementation.createHTMLDocument('detached')

    await expect(openComposer(detached, 'task')).resolves.toBe(false)
  })
})
