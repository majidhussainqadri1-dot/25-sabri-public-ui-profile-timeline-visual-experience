'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/future-public-experience.js'), 'utf8');
const begin = '// V12.3-W03-MOBILE-FOCUS-BOUNDARY-BEGIN';
const end = '// V12.3-W03-MOBILE-FOCUS-BOUNDARY-END';
const first = source.indexOf(begin), last = source.indexOf(end);
assert(first >= 0 && last > first, 'Bounded production helper required');
assert.equal(source.indexOf(begin, first + begin.length), -1, 'Only one helper allowed');
const snippet = source.slice(source.indexOf('\n', first + begin.length) + 1, last);
const wrap = vm.runInNewContext(snippet + '\n actionSheetBoundaryTarget;', {Array}, {timeout: 1000});
assert.equal(typeof wrap, 'function');
const firstAction={id:'first'}, lastAction={id:'last'}, actions=[firstAction,lastAction];
assert.equal(wrap(actions,firstAction,true),lastAction);
assert.equal(wrap(actions,lastAction,false),firstAction);
assert.equal(wrap(actions,firstAction,false),null);
assert.equal(wrap(actions,lastAction,true),null);
assert.equal(wrap(actions,{},false),firstAction);
assert.equal(wrap(actions,{},true),lastAction);
assert.equal(wrap([],firstAction,false),null);
assert.equal(wrap(null,firstAction,false),null);
for(const phrase of [
 "actions.setAttribute('role', 'dialog')",
 "actions.setAttribute('aria-label', t('actionsLabel', 'Profile actions'))",
 "toggle.setAttribute('aria-expanded', 'true')",
 "closeActionSheet(true)",
 "actions.contains(target)",
 "event.key === 'Escape'",
 "if (window.innerWidth > 720 && actions.classList.contains('is-open')) closeActionSheet(false)"
])assert(source.includes(phrase),'Missing mobile action sheet binding: '+phrase);
assert(!source.includes("actions.classList.toggle('is-open', open)"),'Old focus-escape branch cannot remain');
console.log('PASS V12.3 File25: actual mobile dialog focus boundary helper and semantic controls');
