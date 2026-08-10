(() => {
    'use strict';

    const root = document.documentElement;
    const settings = Object.assign({}, window.sabriPublicExperience || {}, window.sabriFuturePublicExperience || {});
    const payloadNode = document.getElementById('spux-future-public-payload');
    if (!payloadNode) return;

    let payload = {};
    try { payload = JSON.parse(payloadNode.textContent || '{}'); } catch (_) { return; }
    if (!payload || typeof payload !== 'object') return;

    root.classList.add('spux-js');
    if ('startViewTransition' in document) root.classList.add('spux-view-transitions-supported');

    const profile = payload.profile || {};
    const storageKey = 'spux_public_preferences_v1';
    const scrollKey = `spux_scroll:${location.pathname}${location.search}`;
    const allowedPrefs = new Set(['reading', 'large-text', 'contrast', 'spacing', 'simple', 'low-data']);

    const element = (tag, className = '', text = '') => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    };
    const safeUrl = (value) => {
        try {
            const url = new URL(String(value || ''), location.origin);
            if (url.origin !== location.origin) return '';
            return url.href;
        } catch (_) { return ''; }
    };
    const canonicalHref = (value) => {
        const safe = safeUrl(value);
        if (!safe) return '';
        const url = new URL(safe);
        url.hash = '';
        return url.href;
    };
    const announce = (message) => {
        const status = document.querySelector('[data-spux-pref-status]');
        if (!status) return;
        status.textContent = '';
        setTimeout(() => { status.textContent = message; }, 20);
    };

    const readPrefs = () => {
        try {
            const parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
        } catch (_) { return {}; }
    };
    const writePrefs = (prefs) => {
        try { localStorage.setItem(storageKey, JSON.stringify(prefs)); } catch (_) {}
    };
    const applyPrefs = (prefs) => {
        allowedPrefs.forEach((key) => {
            root.classList.toggle(`spux-pref-${key}`, prefs[key] === true);
            document.querySelectorAll(`[data-spux-pref="${key}"]`).forEach((button) => {
                button.setAttribute('aria-pressed', prefs[key] === true ? 'true' : 'false');
            });
        });
        if (prefs['low-data'] === true) {
            document.querySelectorAll('video,audio').forEach((media) => {
                media.autoplay = false;
                media.preload = 'none';
                try { media.pause(); } catch (_) {}
            });
            document.querySelectorAll('img:not([fetchpriority="high"])').forEach((image) => {
                image.loading = 'lazy';
                image.decoding = 'async';
            });
        }
    };

    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const initialPrefs = readPrefs();
    if (connection && connection.saveData === true && initialPrefs['low-data'] !== false) initialPrefs['low-data'] = true;

    const toolbar = element('section', 'spux-experience-toolbar');
    toolbar.setAttribute('aria-label', 'Reading and accessibility preferences');
    const controls = element('div', 'spux-experience-toolbar__controls');
    const labels = {
        reading: 'Reading mode', 'large-text': 'Larger text', contrast: 'High contrast',
        spacing: 'More spacing', simple: 'Simplified view', 'low-data': 'Low data',
    };
    Object.entries(labels).forEach(([key, label]) => {
        const button = element('button', 'spux-pref-toggle', label);
        button.type = 'button';
        button.dataset.spuxPref = key;
        button.setAttribute('aria-pressed', 'false');
        controls.appendChild(button);
    });
    const reset = element('button', 'spux-pref-reset', 'Reset');
    reset.type = 'button';
    reset.dataset.spuxPrefReset = '';
    controls.appendChild(reset);
    toolbar.appendChild(controls);

    if (payload.owner_tools === true) {
        const preview = element('details', 'spux-privacy-preview');
        const summary = element('summary', '', 'Privacy preview simulator');
        const links = element('div', 'spux-privacy-preview__links');
        const previewLabels = {
            public: 'Public visitor', member: 'Logged-in member', contact: 'Contact presentation',
            search: 'Search-result presentation', social: 'Social-preview presentation',
            mobile: 'Mobile presentation', desktop: 'Desktop presentation',
        };
        Object.entries(payload.preview_links || {}).forEach(([mode, value]) => {
            const url = safeUrl(value);
            if (!url || !previewLabels[mode]) return;
            const link = element('a', '', previewLabels[mode]);
            link.href = url;
            links.appendChild(link);
        });
        preview.append(summary, links, element('p', '', 'Presentation simulation only; it never grants another role or bypasses authorization.'));
        toolbar.appendChild(preview);

        const quality = payload.quality || {};
        if (Number.isFinite(Number(quality.score))) {
            const details = element('details', 'spux-quality-score');
            details.appendChild(element('summary', '', `Public Experience Quality: ${Number(quality.score)}%`));
            const list = element('ul');
            Object.entries(quality.checks || {}).forEach(([key, pass]) => {
                const item = element('li', '', `${key.replaceAll('_', ' ')}: ${pass === true ? 'Pass' : 'Review'}`);
                item.dataset.status = pass === true ? 'pass' : 'review';
                list.appendChild(item);
            });
            details.append(list, element('p', '', 'Internal quality aid only; it has no public ranking, donation, verification, or visibility effect.'));
            toolbar.appendChild(details);
        }
    }
    const status = element('p', 'spux-sr-only');
    status.dataset.spuxPrefStatus = '';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    toolbar.appendChild(status);

    const hero = document.querySelector('.spux-hero');
    if (hero) hero.insertAdjacentElement('afterend', toolbar);

    const trust = payload.trust || {};
    if (hero && (trust.label || trust.source_label)) {
        const capsule = element('aside', 'spux-trust-capsule');
        capsule.setAttribute('aria-label', 'Profile trust information');
        if (trust.label) capsule.appendChild(element('strong', '', String(trust.label)));
        if (trust.source_label) capsule.appendChild(element('span', '', String(trust.source_label)));
        if (trust.verified_at) {
            const time = element('time', '', `Verified: ${new Date(trust.verified_at).toLocaleDateString()}`);
            time.dateTime = trust.verified_at;
            capsule.appendChild(time);
        }
        if (trust.freshness) capsule.appendChild(element('span', 'spux-trust-capsule__freshness', String(trust.freshness)));
        if (['corrected', 'retracted', 'archived'].includes(trust.correction_state)) {
            capsule.appendChild(element('span', `spux-badge spux-badge--${trust.correction_state}`, trust.correction_state));
        }
        toolbar.insertAdjacentElement('afterend', capsule);
    }

    const actions = document.querySelector('.spux-hero .spux-actions');
    if (actions) {
        if (!actions.id) actions.id = 'spux-profile-actions';
        const toggle = element('button', 'spux-button spux-button--secondary spux-action-sheet-toggle', settings.actionsLabel || 'Profile actions');
        toggle.type = 'button';
        toggle.dataset.spuxActionSheetToggle = '';
        toggle.setAttribute('aria-controls', actions.id);
        toggle.setAttribute('aria-expanded', 'false');
        actions.parentNode?.insertBefore(toggle, actions);

        const shareUrl = safeUrl(profile.url);
        if (shareUrl && profile.name) {
            const share = element('details', 'spux-share-studio');
            const shareSummary = element('summary', 'spux-button spux-button--secondary', 'Share Studio');
            const panel = element('div', 'spux-share-studio__panel');
            const copy = element('button', 'spux-button spux-button--secondary', 'Copy clean link');
            copy.type = 'button';
            copy.dataset.spuxCopyUrl = shareUrl;
            const print = element('button', 'spux-button spux-button--secondary', 'Print profile card');
            print.type = 'button';
            print.dataset.spuxPrintProfile = '';
            const urlText = element('p', 'spux-share-studio__url', shareUrl);
            urlText.dir = 'ltr';
            const snapshot = element('article', 'spux-snapshot-card');
            snapshot.dataset.spuxSnapshotCard = '';
            if (safeUrl(profile.avatar)) {
                const image = document.createElement('img');
                image.src = safeUrl(profile.avatar);
                image.alt = '';
                image.width = 88;
                image.height = 88;
                image.loading = 'lazy';
                snapshot.appendChild(image);
            }
            const body = element('div');
            body.appendChild(element('h3', '', String(profile.name)));
            if (profile.verified === true) body.appendChild(element('p', 'spux-badge', String(profile.role_label || 'Verified')));
            if (profile.headline) body.appendChild(element('p', '', String(profile.headline)));
            const place = [profile.city, profile.country].filter(Boolean).join(', ');
            if (place) body.appendChild(element('p', '', place));
            const clean = element('p', '', shareUrl); clean.dir = 'ltr'; body.appendChild(clean);
            snapshot.appendChild(body);
            panel.append(copy, print, urlText, snapshot);
            share.append(shareSummary, panel);
            actions.appendChild(share);
        }
    }

    const main = document.querySelector('.spux-main');
    if (main && payload.section === 'overview') {
        const fresh = payload.freshness || {};
        if (fresh.updated_at) {
            const p = element('p', 'spux-content-freshness');
            const time = element('time', '', fresh.label || `Public information updated ${new Date(fresh.updated_at).toLocaleDateString()}`);
            time.dateTime = fresh.updated_at;
            p.appendChild(time);
            main.appendChild(p);
        }

        const translations = Array.isArray(payload.translations) ? payload.translations : [];
        if (translations.length) {
            const section = element('section', 'spux-card spux-translation-panel');
            section.dataset.spuxTranslations = '';
            section.appendChild(element('h2', '', 'Translations'));
            const switcher = element('div', 'spux-translation-panel__switcher');
            switcher.setAttribute('role', 'group');
            translations.forEach((row) => {
                const button = element('button', '', String(row.label || row.code));
                button.type = 'button';
                button.dataset.spuxTranslationTarget = String(row.code || '');
                button.setAttribute('aria-pressed', 'false');
                switcher.appendChild(button);
            });
            const bilingual = element('button', '', 'Side by side');
            bilingual.type = 'button'; bilingual.dataset.spuxBilingualToggle = ''; bilingual.setAttribute('aria-pressed', 'false');
            switcher.appendChild(bilingual);
            const content = element('div', 'spux-translation-panel__content');
            translations.forEach((row) => {
                const article = element('article');
                article.lang = String(row.code || '');
                article.dataset.spuxTranslation = String(row.code || '');
                article.hidden = true;
                if (row.headline) article.appendChild(element('h3', '', String(row.headline)));
                if (row.bio) article.appendChild(element('p', '', String(row.bio)));
                content.appendChild(article);
            });
            section.append(switcher, content, element('p', 'spux-translation-note', 'Translations are provider-supplied presentation; the original profile remains authoritative.'));
            main.appendChild(section);
        }

        const relationships = Array.isArray(payload.relationships) ? payload.relationships : [];
        if (relationships.length) {
            const section = element('section', 'spux-card spux-relationship-explorer');
            section.appendChild(element('h2', '', 'Knowledge Relationships'));
            const list = element('ul');
            relationships.forEach((row) => {
                const url = safeUrl(row.url); if (!url) return;
                const item = element('li'); item.dataset.spuxRelationType = String(row.type || 'related');
                const link = element('a'); link.href = url; link.appendChild(element('strong', '', String(row.label || 'Related item'))); item.appendChild(link);
                if (row.relationship) item.appendChild(element('span', '', String(row.relationship)));
                list.appendChild(item);
            });
            section.appendChild(list); main.appendChild(section);
        }

        const citations = Array.isArray(payload.citations) ? payload.citations : [];
        if (citations.length) {
            const details = element('details', 'spux-card spux-citation-drawer');
            details.appendChild(element('summary', '', 'References and citations'));
            const list = element('ol');
            citations.forEach((row) => {
                const url = safeUrl(row.url); if (!url) return;
                const item = element('li'); const link = element('a', '', String(row.title || 'Reference')); link.href = url; item.appendChild(link);
                if (row.source) item.appendChild(document.createTextNode(` — ${row.source}`));
                list.appendChild(item);
            });
            details.appendChild(list); main.appendChild(details);
        }
    }

    const timelineMeta = Array.isArray(payload.timeline) ? payload.timeline : [];
    const metaByUrl = new Map(timelineMeta.map((row) => [canonicalHref(row.url), row]));
    const timelineCards = [...document.querySelectorAll('.spux-timeline-card')];
    timelineCards.forEach((card) => {
        card.dataset.spuxTimelineCard = '';
        const link = card.querySelector('h3 a[href]');
        const meta = link ? metaByUrl.get(canonicalHref(link.href)) : null;
        if (!meta) return;
        if (meta.source_verified === true && !card.querySelector('[data-spux-source-verified]')) {
            const provenance = element('div', 'spux-provenance');
            provenance.setAttribute('aria-label', 'Content provenance');
            const badge = element('span', 'spux-badge spux-badge--provenance', settings.sourceVerified || 'Verified source projection');
            badge.dataset.spuxSourceVerified = '';
            provenance.appendChild(badge);
            card.querySelector('.spux-card__meta')?.after(provenance);
        }
        if (meta.updated_at && !card.querySelector('.spux-item-freshness')) {
            const wrap = element('span', 'spux-item-freshness');
            const date = new Date(meta.updated_at);
            const time = element('time', '', Number.isNaN(date.getTime()) ? (settings.updatedLabel || 'Updated') : `${settings.updatedLabel || 'Updated'} ${date.toLocaleDateString()}`);
            time.dateTime = meta.updated_at; wrap.appendChild(time); card.querySelector('.spux-card__meta')?.appendChild(wrap);
        }
    });

    if (timelineCards.length) {
        const yearMap = new Map();
        timelineCards.forEach((card) => {
            const datetime = card.querySelector('time[datetime]')?.getAttribute('datetime') || '';
            const match = datetime.match(/^(19|20)\d{2}/); if (!match) return;
            card.dataset.spuxTimelineYear = match[0]; yearMap.set(match[0], (yearMap.get(match[0]) || 0) + 1);
        });
        const years = [...yearMap.keys()].sort((a, b) => Number(b) - Number(a));
        const heading = document.querySelector('.spux-section-heading');
        if (years.length && heading) {
            const navigator = element('div', 'spux-time-navigator'); navigator.dataset.spuxTimeNavigator = '';
            const nav = element('nav', 'spux-time-navigator__nav'); nav.setAttribute('aria-label', settings.timelineYears || 'Timeline years');
            const makeButton = (year, label) => { const b = element('button', 'spux-time-navigator__item', label); b.type = 'button'; b.dataset.spuxYear = year; b.setAttribute('aria-pressed', year === '' ? 'true' : 'false'); return b; };
            nav.appendChild(makeButton('', settings.allYears || 'All years')); years.forEach((year) => nav.appendChild(makeButton(year, year)));
            const era = element('details', 'spux-era-summary'); era.appendChild(element('summary', '', settings.eraSummary || 'Timeline era summary'));
            const list = element('ul'); years.forEach((year) => list.appendChild(element('li', '', `${year}: ${yearMap.get(year)} public items on this page`))); era.appendChild(list);
            const navStatus = element('p', 'spux-sr-only'); navStatus.setAttribute('role', 'status'); navStatus.setAttribute('aria-live', 'polite');
            navigator.append(nav, era, navStatus); heading.after(navigator);
            nav.addEventListener('click', (event) => {
                const button = event.target.closest('[data-spux-year]'); if (!button) return;
                const selected = button.dataset.spuxYear || '';
                nav.querySelectorAll('[data-spux-year]').forEach((candidate) => candidate.setAttribute('aria-pressed', candidate === button ? 'true' : 'false'));
                let shown = 0;
                timelineCards.forEach((card) => { const visible = selected === '' || card.dataset.spuxTimelineYear === selected; card.hidden = !visible; if (visible) shown += 1; });
                navStatus.textContent = selected ? `${shown} public items shown for ${selected} on this page.` : `${shown} public items shown on this page.`;
            });
        }
    }

    applyPrefs(initialPrefs);

    document.addEventListener('click', (event) => {
        const pref = event.target.closest('[data-spux-pref]');
        if (pref) {
            const key = pref.dataset.spuxPref || ''; if (!allowedPrefs.has(key)) return;
            const prefs = readPrefs(); prefs[key] = prefs[key] !== true; writePrefs(prefs); applyPrefs(prefs); announce(settings.preferenceUpdated || 'Display preference updated.'); return;
        }
        if (event.target.closest('[data-spux-pref-reset]')) { writePrefs({}); applyPrefs({}); announce(settings.preferencesReset || 'Display preferences reset.'); return; }
        const toggle = event.target.closest('[data-spux-action-sheet-toggle]');
        if (toggle && actions) {
            const open = !actions.classList.contains('is-open'); actions.classList.toggle('is-open', open); toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) setTimeout(() => actions.querySelector('a[href],button:not([disabled]),summary')?.focus(), 0); else toggle.focus(); return;
        }
        const copy = event.target.closest('[data-spux-copy-url]');
        if (copy) {
            const url = safeUrl(copy.dataset.spuxCopyUrl); if (!url) return;
            const done = () => announce(settings.linkCopied || 'Link copied.');
            if (navigator.clipboard?.writeText && window.isSecureContext) navigator.clipboard.writeText(url).then(done).catch(() => {});
            else { const field = document.createElement('textarea'); field.value = url; field.readOnly = true; field.style.position = 'fixed'; field.style.insetInlineStart = '-9999px'; document.body.appendChild(field); field.select(); try { document.execCommand('copy'); done(); } catch (_) {} field.remove(); }
            return;
        }
        if (event.target.closest('[data-spux-print-profile]')) { root.classList.add('spux-print-snapshot'); window.print(); setTimeout(() => root.classList.remove('spux-print-snapshot'), 250); }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !actions?.classList.contains('is-open')) return;
        actions.classList.remove('is-open');
        const toggle = document.querySelector('[data-spux-action-sheet-toggle]'); if (toggle) { toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); }
    });

    const translationRoot = document.querySelector('[data-spux-translations]');
    if (translationRoot) {
        const buttons = [...translationRoot.querySelectorAll('[data-spux-translation-target]')];
        const panels = [...translationRoot.querySelectorAll('[data-spux-translation]')];
        const bilingual = translationRoot.querySelector('[data-spux-bilingual-toggle]');
        buttons.forEach((button) => button.addEventListener('click', () => {
            translationRoot.classList.remove('is-bilingual'); bilingual?.setAttribute('aria-pressed', 'false');
            buttons.forEach((b) => b.setAttribute('aria-pressed', b === button ? 'true' : 'false'));
            panels.forEach((panel) => { panel.hidden = panel.dataset.spuxTranslation !== button.dataset.spuxTranslationTarget; });
        }));
        bilingual?.addEventListener('click', () => {
            const enabled = !translationRoot.classList.contains('is-bilingual'); translationRoot.classList.toggle('is-bilingual', enabled); bilingual.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            panels.forEach((panel) => { panel.hidden = !enabled; }); if (enabled) buttons.forEach((b) => b.setAttribute('aria-pressed', 'false'));
        });
    }

    const saveScroll = () => { try { sessionStorage.setItem(scrollKey, JSON.stringify({ y: Math.max(0, Math.round(scrollY)), at: Date.now() })); } catch (_) {} };
    addEventListener('pagehide', saveScroll, { capture: true });
    addEventListener('pageshow', (event) => {
        const nav = performance.getEntriesByType?.('navigation')?.[0]; if (!(event.persisted || nav?.type === 'back_forward')) return;
        try { const state = JSON.parse(sessionStorage.getItem(scrollKey) || '{}'); if (Number.isFinite(state.y) && Date.now() - Number(state.at || 0) < 3600000) requestAnimationFrame(() => scrollTo({ top: state.y, left: 0, behavior: 'auto' })); } catch (_) {}
    });

    if (payload.owner_tools === true) {
        const issues = [];
        document.querySelectorAll('img').forEach((image) => { if (!image.hasAttribute('alt')) issues.push('image_missing_alt'); });
        document.querySelectorAll('a[href],button').forEach((control) => { const box = control.getBoundingClientRect(); if (box.width > 0 && box.height > 0 && (box.width < 24 || box.height < 24)) issues.push('small_interactive_target'); });
        if (root.scrollWidth > root.clientWidth + 2) issues.push('horizontal_overflow');
        const unique = [...new Set(issues)];
        dispatchEvent(new CustomEvent('spux:visual-integrity', { detail: { status: unique.length ? 'review' : 'pass', issues: unique, issueCount: unique.length } }));
        root.dataset.spuxVisualIntegrity = unique.length ? 'review' : 'pass';
    }
})();
