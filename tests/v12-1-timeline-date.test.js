'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Execute the production pure date-choice function itself, with no synthetic
// browser DOM, network, or runtime authority claims.
const source = fs.readFileSync(path.join(__dirname, '../assets/js/future-public-experience.js'), 'utf8');
const startTag = '// V12-026-DATE-CHOICE-BEGIN';
const endTag = '// V12-026-DATE-CHOICE-END';
const first = source.indexOf(startTag);
const last = source.indexOf(endTag, first);
assert(first >= 0 && last > first, 'Date helper must have one auditable source span.');
assert.equal(source.indexOf(startTag, first + startTag.length), -1, 'Only one date helper declaration is permitted.');
const snippet = source.slice(first + startTag.length, last);
assert(!snippet.includes('Date.now()'), 'A visitor device clock must not override server-vetted source dates.');
const choose = vm.runInNewContext(`${snippet}\n timelineDatePresentation;`, {Date, Number}, {timeout: 1500});
assert.equal(typeof choose, 'function');

const published = '2026-10-01T10:00:00Z';
const newer = '2026-10-02T12:00:00Z';
const older = '2026-09-30T09:00:00Z';
const onlyPublished = choose({published_at: published});
assert.equal(onlyPublished.kind, 'published');
assert.equal(onlyPublished.stamp, published);
assert.equal(choose({published_at: published, updated_at: newer}).kind, 'updated');
assert.equal(choose({published_at: published, updated_at: published}).kind, 'published');
assert.equal(choose({published_at: published, updated_at: older}).kind, 'published');
assert.equal(choose({published_at: published, updated_at: 'nonsense'}).kind, 'published');
assert.equal(choose({published_at: published, updated_at: '2026-10-01T15:00:00+05:00'}).kind, 'published');
assert.equal(choose({published_at: published, updated_at: '2026-10-01T15:00:01+05:00'}).kind, 'updated');
assert.equal(choose({updated_at: newer}).kind, 'updated');
assert.equal(choose({published_at: 'not-a-date'}), null);
assert.equal(choose({}), null);
assert.equal(choose(null), null);

assert(source.includes("t('publishedLabel', 'Published')"), 'Published must be localized.');
assert(source.includes("t('updatedLabel', 'Updated')"), 'Updated must be localized.');
assert(source.includes("existingDate.getTime() === datePresentation.date.getTime()"),
    'The existing canonical publication date must be reused rather than displayed twice.');
assert(source.includes("existing.textContent = `${label} ${existing.textContent.trim()}`;"),
    'Published must label its pre-rendered time element in place.');
console.log('PASS V12.1: actual JS source helper distinguishes Published/Updated/timezone without client-clock gate');
