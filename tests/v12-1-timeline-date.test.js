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
const sourceBodyStart = source.indexOf('\n', first + startTag.length);
assert(sourceBodyStart > first && sourceBodyStart < last, 'Date helper must start after its declaration comment.');
const snippet = source.slice(sourceBodyStart + 1, last);
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

// Execute the actual URL-collision fail-closed helper as well.
const duplicateStart = source.indexOf('// V12-012-UNIQUE-METADATA-BEGIN');
const duplicateEnd = source.indexOf('// V12-012-UNIQUE-METADATA-END', duplicateStart);
assert(duplicateStart >= 0 && duplicateEnd > duplicateStart);
const duplicateBodyStart = source.indexOf('\n', duplicateStart);
const uniqueSource = source.slice(duplicateBodyStart + 1, duplicateEnd);
const uniqueIndex = vm.runInNewContext(`${uniqueSource}\n indexUniqueTimelineMetadata;`, {Map, Set, Array}, {timeout: 1500});
const canonical = (url) => typeof url === 'string' && url.startsWith('https://example.test/') ? url : '';
const a = {url: 'https://example.test/same/', source_verified: false};
const b = {url: 'https://example.test/same/', source_verified: true};
const c = {url: 'https://example.test/unique/', source_verified: true};
const map = uniqueIndex([a, b, c, {url:'https://external.test/'}, a], canonical);
assert(!map.has(a.url), 'Conflicting native objects must never acquire a last-writer verified badge.');
assert.equal(map.get(c.url), c, 'Unique canonical URL projection must be preserved.');
assert.equal(map.size, 1, 'Repeated collision must remain suppressed, not recreated.');
assert.equal(uniqueIndex([], canonical).size, 0);
assert.equal(uniqueIndex(null, canonical).size, 0);
assert(source.includes("t('publishedLabel', 'Published')"), 'Published must be localized.');
assert(source.includes("t('updatedLabel', 'Updated')"), 'Updated must be localized.');
assert(source.includes("datePresentation.date.toLocaleDateString(undefined, {timeZone: 'UTC'})"), 'Updated dates must share the canonical UTC calendar day of server-rendered publications.');
assert(source.includes("t('sourceVerified', 'Source reports verified status')"), 'A source badge must not claim independent platform verification.');
assert(source.includes("existingDate.getTime() === datePresentation.date.getTime()"),
    'The existing canonical publication date must be reused rather than displayed twice.');
assert(source.includes("existing.textContent = `${label} ${existing.textContent.trim()}`;"),
    'Published must label its pre-rendered time element in place.');
console.log('PASS V12.1: actual JS source helper distinguishes Published/Updated/timezone without client-clock gate');
