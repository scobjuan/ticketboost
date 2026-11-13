import { describe, expect, it } from 'vitest'

import { detectContext, ticketId } from '../../src/context/detect'

describe('detectContext', () => {
  it.each([
    ['/front/ticket.form.php', '?id=12', 'ticket'],
    ['/glpi/front/ticket.form.php', '?foo=1&id=3', 'ticket'],
    ['/front/ticket.form.php', '?id=0', 'other'],
    ['/front/ticket.form.php', '?id=abc', 'other'],
    ['/front/ticket.form.php', '?id=-4', 'other'],
    ['/front/ticket.form.php', '?id=12abc', 'other'],
    ['/front/ticket.form.php', '', 'other'],
    ['/front/ticket.php', '?id=12', 'other'],
    ['/front/ticket.form.php.bak', '?id=12', 'other'],
    ['/front/change.form.php', '?id=12', 'other'],
    ['/', '', 'other']
  ])('%s%s is %s', (pathname, search, expected) => {
    expect(detectContext({ pathname, search })).toBe(expected)
  })
})

describe('ticketId', () => {
  it.each([
    ['/front/ticket.form.php', '?id=12', 12],
    ['/glpi/front/ticket.form.php', '?foo=1&id=3', 3],
    ['/front/ticket.form.php', '?id=0', null],
    ['/front/ticket.form.php', '?id=12abc', null],
    ['/front/ticket.form.php', '', null],
    ['/front/ticket.php', '?id=12', null]
  ])('%s%s gives %s', (pathname, search, expected) => {
    expect(ticketId({ pathname, search })).toBe(expected)
  })
})
