// Fails when a gzipped frontend bundle exceeds its budget (RULES.md section 3, ADR 0019).
import { existsSync, readFileSync } from 'node:fs'
import { gzipSync } from 'node:zlib'

const BUNDLES = [
  {
    name: 'main',
    budget: 15 * 1024,
    files: ['public/js/ticketboost.js', 'public/css/ticketboost.css']
  },
  {
    name: 'config',
    budget: 10 * 1024,
    files: ['public/js/ticketboost-config.js']
  }
]

let failed = false
for (const { name, budget, files } of BUNDLES) {
  let total = 0
  for (const file of files) {
    if (!existsSync(file)) {
      console.error(`${file} not found, run "pnpm build" first`)
      process.exit(1)
    }
    const size = gzipSync(readFileSync(file)).length
    total += size
    console.log(`${file}: ${size} B gzip`)
  }
  if (total > budget) {
    console.error(`The ${name} bundle is ${total} B gzip (budget ${budget} B)`)
    failed = true
  } else {
    console.log(`The ${name} bundle is ok: ${total}/${budget} B gzip`)
  }
}
process.exit(failed ? 1 : 0)
