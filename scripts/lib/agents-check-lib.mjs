// Pure checks used by scripts/agents-check.mjs. Every function returns a list of
// human readable problems; an empty list means the input is valid.
import { createHash } from 'node:crypto'

/**
 * SHA-256 of lowercase names of coding tools and vendors that must never appear in
 * the repository, commits or pull requests. Stored as hashes so the repository never
 * contains the names in plain text. To add one, append sha256(lowercase name).
 */
export const FORBIDDEN_TOKEN_HASHES = new Set([
  'c857d09db23e6822e3600bc06ad8d58f92ed62bc8efd81c753f77048662cb97d',
  'c70eca6b0f88f44d81a41311647e50fda1ac454ec04ffd442b0eb4743a993131',
  '57de4cf40144bdf7d00010f2f5557a7d642c2b9705309bfade167dd313e2ca93',
  '7d3194f79e645c42e4396dda38be04766810ec6a00d00aced3ffc2a0a1f1a9ef',
  '60965168ce762e949600281ba6d01fee136e5b6e8257b1f216f9025ed324474c',
  '5d72436256ada53828b51895a94bb8489e9f1ac4fe937a8024ef1594e7045ff6',
  '3ea125d0bff386e6754b3782b300016fc79a9cf8f8669c0a5c3db64467ddb681',
  '5d0c0ab127fdea24d94e7e3326b6ceb965ab82ad897f9de1bd6b1a9a23f87578'
])

/** Attribution phrases that must never appear (trailers and footers added by tools). */
export const FORBIDDEN_PHRASES = [/co-authored-by\s*:/i, /generated\s+with\b/i]

/** Pictographic symbols that are allowed because they are not emojis. */
const ALLOWED_PICTOGRAPHS = new Set(['©', '®', '™'])

const EMOJI = /[\p{Extended_Pictographic}\u{1F1E6}-\u{1F1FF}]/gu

/**
 * @param {string} value
 * @returns {string} Hex SHA-256 digest.
 */
export function sha256(value) {
  return createHash('sha256').update(value).digest('hex')
}

/**
 * Finds emojis in a text.
 * @param {string} text
 * @returns {string[]} Problems, one per offending line.
 */
export function findEmojis(text) {
  const problems = []
  text.split('\n').forEach((line, index) => {
    const found = [...line.matchAll(EMOJI)]
      .map((match) => match[0])
      .filter((char) => !ALLOWED_PICTOGRAPHS.has(char))
    if (found.length > 0) {
      const codes = found.map(
        (char) => 'U+' + char.codePointAt(0).toString(16).toUpperCase()
      )
      problems.push(`line ${index + 1}: emoji ${codes.join(', ')}`)
    }
  })
  return problems
}

/**
 * Finds tool attribution: forbidden phrases and forbidden tool names.
 * @param {string} text
 * @param {Set<string>} [hashes] Forbidden token hashes (injectable for tests).
 * @returns {string[]} Problems, one per offending line.
 */
export function findAttribution(text, hashes = FORBIDDEN_TOKEN_HASHES) {
  const problems = []
  text.split('\n').forEach((line, index) => {
    for (const phrase of FORBIDDEN_PHRASES) {
      if (phrase.test(line)) {
        problems.push(`line ${index + 1}: attribution phrase ${phrase.source}`)
      }
    }
    const tokens = line.toLowerCase().match(/[a-z0-9]+/g) ?? []
    if (tokens.some((token) => hashes.has(sha256(token)))) {
      problems.push(`line ${index + 1}: tool or vendor name`)
    }
  })
  return problems
}

/**
 * Validates a capped agent file. Sections declare their own cap in the heading,
 * for example "## Current state (max 20)"; non-empty lines below it are counted.
 * @param {string} text File contents.
 * @param {{maxLines: number, required: string[]}} options
 * @returns {string[]} Problems.
 */
export function checkCappedFile(text, { maxLines, required }) {
  const problems = []
  const lines = text.replace(/\n$/, '').split('\n')
  if (lines.length > maxLines) {
    problems.push(`${lines.length} lines (max ${maxLines})`)
  }

  const counts = new Map()
  const caps = new Map()
  let current = null
  for (const line of lines) {
    const heading = /^## (.+?)(?: \(max (\d+)\))?\s*$/.exec(line)
    if (heading) {
      current = heading[1]
      counts.set(current, 0)
      if (heading[2]) caps.set(current, Number(heading[2]))
      continue
    }
    if (current && line.trim() !== '')
      counts.set(current, counts.get(current) + 1)
  }

  for (const name of required) {
    if (!counts.has(name)) problems.push(`missing section "## ${name}"`)
  }
  for (const [name, cap] of caps) {
    if (counts.get(name) > cap) {
      problems.push(
        `section "${name}" has ${counts.get(name)} lines (max ${cap})`
      )
    }
  }
  return problems
}

/**
 * Parses a gettext .po file into msgid/msgstr pairs (header entry excluded).
 * @param {string} text
 * @returns {{msgid: string, msgstr: string}[]}
 */
export function parsePo(text) {
  const entries = []
  let entry = null
  let field = null
  const unquote = (value) => JSON.parse(value)
  for (const raw of text.split('\n')) {
    const line = raw.trim()
    let match
    if ((match = /^msgid (".*")$/.exec(line))) {
      if (entry) entries.push(entry)
      entry = { msgid: unquote(match[1]), msgstr: '' }
      field = 'msgid'
    } else if ((match = /^msgstr(?:\[0\])? (".*")$/.exec(line)) && entry) {
      entry.msgstr = unquote(match[1])
      field = 'msgstr'
    } else if (/^".*"$/.test(line) && entry && field) {
      entry[field] += unquote(line)
    } else if (line.startsWith('msgid_plural') || line.startsWith('msgstr[')) {
      field = null
    }
  }
  if (entry) entries.push(entry)
  return entries.filter((item) => item.msgid !== '')
}

/**
 * Lists msgids without translation.
 * @param {string} text Contents of a .po file.
 * @returns {string[]} Problems.
 */
export function findUntranslated(text) {
  return parsePo(text)
    .filter((entry) => entry.msgstr.trim() === '')
    .map((entry) => `untranslated msgid "${entry.msgid}"`)
}

/**
 * Validates that a markdown file starts with a frontmatter block holding the keys.
 * @param {string} text
 * @param {string[]} keys
 * @returns {string[]} Problems.
 */
export function checkFrontmatter(text, keys) {
  const match = /^---\n([\s\S]*?)\n---\n/.exec(text)
  if (!match) return ['missing frontmatter block']
  const present = new Set(
    match[1]
      .split('\n')
      .map((line) => /^([a-z_]+):/.exec(line)?.[1])
      .filter(Boolean)
  )
  return keys
    .filter((key) => !present.has(key))
    .map((key) => `missing frontmatter key "${key}"`)
}

/**
 * Removes comment lines git adds to commit message templates.
 * @param {string} message
 * @returns {string}
 */
export function stripCommitComments(message) {
  const scissors = message.indexOf(
    '# ------------------------ >8 ------------------------'
  )
  const body = scissors === -1 ? message : message.slice(0, scissors)
  return body
    .split('\n')
    .filter((line) => !line.startsWith('#'))
    .join('\n')
    .trimEnd()
}
