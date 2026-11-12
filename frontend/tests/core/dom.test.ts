import { describe, expect, it } from 'vitest'

import { h, replaceChildren } from '../../src/core/dom'

describe('h', () => {
  it('sets string and boolean attributes and skips false or undefined', () => {
    const element = h(document, 'input', {
      type: 'text',
      disabled: true,
      readonly: false,
      title: undefined
    })

    expect(element.getAttribute('type')).toBe('text')
    expect(element.hasAttribute('disabled')).toBe(true)
    expect(element.hasAttribute('readonly')).toBe(false)
    expect(element.hasAttribute('title')).toBe(false)
  })

  it('adds text as text nodes, never as markup', () => {
    const element = h(document, 'div', {}, [
      '<img src=x onerror=alert(1)>',
      null,
      false,
      undefined,
      h(document, 'span', {}, ['ok'])
    ])

    expect(element.querySelector('img')).toBeNull()
    expect(element.textContent).toBe('<img src=x onerror=alert(1)>ok')
    expect(element.children).toHaveLength(1)
  })

  it('works with defaults', () => {
    expect(h(document, 'p').outerHTML).toBe('<p></p>')
  })
})

describe('replaceChildren', () => {
  it('removes old children and appends new ones', () => {
    const parent = h(document, 'ul', {}, [h(document, 'li', {}, ['old'])])

    replaceChildren(parent, [
      h(document, 'li', {}, ['a']),
      h(document, 'li', {}, ['b'])
    ])

    expect(parent.textContent).toBe('ab')
  })
})
