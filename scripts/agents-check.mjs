// Guards the project rules that tooling can enforce.
//
// Usage:
//   node scripts/agents-check.mjs                 check the working tree
//   node scripts/agents-check.mjs --message FILE  check a commit message (commit-msg hook)
//   node scripts/agents-check.mjs --text-env VAR  check text from an env var (PR title/body in CI)
import { execFileSync } from 'node:child_process'
import { existsSync, lstatSync, readdirSync, readFileSync } from 'node:fs'
import { extname, join } from 'node:path'

import {
  checkCappedFile,
  checkFrontmatter,
  findAttribution,
  findEmojis,
  findUntranslated,
  stripCommitComments
} from './lib/agents-check-lib.mjs'

const CAPPED_FILES = {
  '.agents/MEMORY.md': {
    maxLines: 250,
    required: [
      'Current state',
      'In progress',
      'Next steps',
      'Blockers and waiting on owner',
      'Open questions',
      'Environment status',
      'Session log'
    ]
  },
  '.agents/KNOWLEDGE.md': {
    maxLines: 300,
    required: [
      'Existing utilities - USE, do not duplicate',
      'Patterns',
      'GLPI gotchas',
      'Testing learnings',
      'Tooling learnings'
    ]
  }
}

const BINARY_EXTENSIONS = new Set([
  '.png',
  '.jpg',
  '.jpeg',
  '.gif',
  '.ico',
  '.mo',
  '.woff',
  '.woff2',
  '.zip',
  '.bz2',
  '.gz'
])
const SKIP_FILES = new Set(['pnpm-lock.yaml', 'composer.lock'])

/** @returns {string[]} Tracked and new (not ignored) files. */
function repositoryFiles() {
  const output = execFileSync(
    'git',
    ['ls-files', '--cached', '--others', '--exclude-standard', '-z'],
    {
      encoding: 'utf8'
    }
  )
  return output
    .split('\0')
    .filter(Boolean)
    .filter(
      (file) =>
        existsSync(file) &&
        !lstatSync(file).isSymbolicLink() &&
        lstatSync(file).isFile()
    )
}

function checkTree() {
  const problems = []
  const add = (file, list) =>
    list.forEach((problem) => problems.push(`${file}: ${problem}`))

  for (const file of repositoryFiles()) {
    if (BINARY_EXTENSIONS.has(extname(file)) || SKIP_FILES.has(file)) continue
    const text = readFileSync(file, 'utf8')
    if (text.includes('\0')) continue
    add(file, findEmojis(text))
    add(file, findAttribution(text))
  }

  for (const [file, options] of Object.entries(CAPPED_FILES)) {
    if (existsSync(file))
      add(file, checkCappedFile(readFileSync(file, 'utf8'), options))
    else problems.push(`${file}: missing`)
  }

  const spanish = 'locales/es_ES.po'
  if (existsSync(spanish))
    add(spanish, findUntranslated(readFileSync(spanish, 'utf8')))
  else problems.push(`${spanish}: missing`)

  const frontmatter = {
    '.agents/decisions': ['id', 'title', 'status', 'date'],
    '.agents/playbooks': ['title', 'when']
  }
  for (const [dir, keys] of Object.entries(frontmatter)) {
    if (!existsSync(dir)) continue
    for (const name of readdirSync(dir).filter(
      (entry) => entry.endsWith('.md') && entry !== 'README.md'
    )) {
      const file = join(dir, name)
      add(file, checkFrontmatter(readFileSync(file, 'utf8'), keys))
    }
  }
  return problems
}

function checkText(label, text, { emojis }) {
  const problems = findAttribution(text).map(
    (problem) => `${label}: ${problem}`
  )
  if (emojis)
    problems.push(...findEmojis(text).map((problem) => `${label}: ${problem}`))
  return problems
}

const args = process.argv.slice(2)
let problems
if (args[0] === '--message' && args[1]) {
  // Emojis are allowed in commit messages (Conventional Commits), attribution is not.
  problems = checkText(
    'commit message',
    stripCommitComments(readFileSync(args[1], 'utf8')),
    { emojis: false }
  )
} else if (args[0] === '--text-env' && args[1]) {
  problems = checkText(args[1], process.env[args[1]] ?? '', { emojis: true })
} else if (args.length === 0) {
  problems = checkTree()
} else {
  console.error('Usage: agents-check.mjs [--message FILE | --text-env VAR]')
  process.exit(2)
}

if (problems.length > 0) {
  console.error(problems.join('\n'))
  console.error(
    `\nagents:check failed with ${problems.length} problem(s). See .agents/RULES.md.`
  )
  process.exit(1)
}
console.log('agents:check ok')
