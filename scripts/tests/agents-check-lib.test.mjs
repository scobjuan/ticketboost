import { describe, expect, it } from 'vitest'

import {
  checkCappedFile,
  checkFrontmatter,
  findAttribution,
  findEmojis,
  findUntranslated,
  FORBIDDEN_TOKEN_HASHES,
  parsePo,
  sha256,
  stripCommitComments
} from '../lib/agents-check-lib.mjs'

describe('findEmojis', () => {
  it('accepts plain text and legal symbols', () => {
    expect(findEmojis('Copyright © 2026 TicketBoost™')).toEqual([])
  })

  it('reports emojis with line numbers and code points', () => {
    expect(findEmojis('ok\nship it \u{1F680}')).toEqual([
      'line 2: emoji U+1F680'
    ])
  })
})

describe('findAttribution', () => {
  const custom = new Set([sha256('acmebot')])

  it('reports forbidden tool names by hash, case insensitive', () => {
    expect(findAttribution('Built by AcmeBot.md', custom)).toEqual([
      'line 1: tool or vendor name'
    ])
  })

  it('does not report names that only contain a forbidden token as a substring', () => {
    expect(findAttribution('acmebotanical garden', custom)).toEqual([])
  })

  it('reports attribution trailers and footers', () => {
    const trailer = ['Co', 'Authored', 'By: Someone'].join('-')
    const footer = ['Generated', 'with', 'something'].join(' ')
    expect(findAttribution(`${trailer}\n${footer}`, custom)).toHaveLength(2)
  })

  it('ships a non-empty list of sha256 digests', () => {
    expect(FORBIDDEN_TOKEN_HASHES.size).toBeGreaterThan(0)
    for (const hash of FORBIDDEN_TOKEN_HASHES)
      expect(hash).toMatch(/^[0-9a-f]{64}$/)
  })
})

describe('checkCappedFile', () => {
  const file = [
    '# Memory',
    '## State (max 2)',
    '- a',
    '- b',
    '',
    '## Log',
    '- x'
  ].join('\n')

  it('accepts a file within caps', () => {
    expect(
      checkCappedFile(file, { maxLines: 10, required: ['State', 'Log'] })
    ).toEqual([])
  })

  it('reports section caps, total caps and missing sections', () => {
    const over = file.replace('- b', '- b\n- c')
    expect(
      checkCappedFile(over, { maxLines: 3, required: ['Missing'] })
    ).toEqual([
      '8 lines (max 3)',
      'missing section "## Missing"',
      'section "State" has 3 lines (max 2)'
    ])
  })
})

describe('po helpers', () => {
  const po = [
    'msgid ""',
    'msgstr ""',
    '"Language: es_ES\\n"',
    '',
    'msgid "Next ticket"',
    'msgstr "Siguiente ticket"',
    '',
    'msgid ""',
    '"Long "',
    '"text"',
    'msgstr ""',
    '',
    'msgid "One"',
    'msgid_plural "Many"',
    'msgstr[0] "Uno"',
    'msgstr[1] "Muchos"'
  ].join('\n')

  it('parses entries and multiline strings without the header', () => {
    expect(parsePo(po)).toEqual([
      { msgid: 'Next ticket', msgstr: 'Siguiente ticket' },
      { msgid: 'Long text', msgstr: '' },
      { msgid: 'One', msgstr: 'Uno' }
    ])
  })

  it('reports untranslated entries', () => {
    expect(findUntranslated(po)).toEqual(['untranslated msgid "Long text"'])
  })
})

describe('checkFrontmatter', () => {
  it('accepts a complete frontmatter', () => {
    expect(
      checkFrontmatter('---\ntitle: A\nwhen: B\n---\nbody', ['title', 'when'])
    ).toEqual([])
  })

  it('reports missing blocks and keys', () => {
    expect(checkFrontmatter('body', ['title'])).toEqual([
      'missing frontmatter block'
    ])
    expect(checkFrontmatter('---\ntitle: A\n---\n', ['title', 'when'])).toEqual(
      ['missing frontmatter key "when"']
    )
  })
})

describe('stripCommitComments', () => {
  it('drops comment lines and the verbose diff below the scissors line', () => {
    const message = [
      'feat: add x',
      '# Please enter the commit message',
      '# ------------------------ >8 ------------------------',
      'diff --git a/x b/x'
    ].join('\n')
    expect(stripCommitComments(message)).toBe('feat: add x')
  })
})
