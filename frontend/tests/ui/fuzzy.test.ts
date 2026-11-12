import { describe, expect, it } from 'vitest'

import { fuzzyFilter, fuzzyScore } from '../../src/ui/fuzzy'

describe('fuzzyScore', () => {
  it('matches subsequences case-insensitively and ignoring accents', () => {
    expect(fuzzyScore('jgm', 'Jose Garcia Martinez')).toBeGreaterThan(0)
    expect(fuzzyScore('GARCIA', 'José García')).toBeGreaterThan(0)
    expect(fuzzyScore('xyz', 'Jose Garcia')).toBe(0)
  })

  it('accepts everything for an empty query', () => {
    expect(fuzzyScore('', 'anything')).toBe(1)
  })

  it('prefers consecutive characters and word starts', () => {
    expect(fuzzyScore('tech', 'tech1')).toBeGreaterThan(
      fuzzyScore('tech', 'the cheap')
    )
    expect(fuzzyScore('s', 'support@example')).toBeGreaterThan(
      fuzzyScore('s', 'a user')
    )
  })
})

describe('fuzzyFilter', () => {
  it('drops non-matches and sorts best first, keeping ties stable', () => {
    const items = ['Network team', 'tech2', 'tech1', 'Helpdesk']

    expect(fuzzyFilter('tech', items, (item) => item)).toEqual([
      'tech2',
      'tech1'
    ])
    expect(fuzzyFilter('', items, (item) => item)).toEqual(items)
  })
})
