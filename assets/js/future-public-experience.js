(() => {
    'use strict';

    const root = document.documentElement;
    const settings = Object.assign({}, window.sabriPublicExperience || {}, window.sabriFuturePublicExperience || {});
    const payloadNode = document.getElementById('spux-future-public-payload');
    if (!payloadNode) return;

    let payload = {};
    try { payload = JSON.parse(payloadNode.textContent || '{}'); } catch (_) { return; }
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) return;

    const profileRoot = document.querySelector('.spux-profile');
    if (!profileRoot) return;

    root.classList.add('spux-js');
    if ('startViewTransition' in document) root.classList.add('spux-view-transitions-supported');

    const profile = payload.profile || {};
    const storageKey = 'spux_public_preferences_v1';
    const scrollKey = `spux_scroll:${location.pathname}${location.search}`;
    const allowedPrefs = new Set(['reading', 'large-text', 'contrast', 'spacing', 'simple', 'low-data']);

    const t = (key, fallback) => {
        const value = settings[key];
        return typeof value === 'string' && value.trim() !== '' ? value : fallback;
    };
    const element = (tag, className = '', text = '') => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    };
    const safeUrl = (value) => {
        const raw = String(value || '').trim();
        if (!raw || raw.length > 2048 || raw.startsWith('//') || raw.includes('\\') || /[\u0000-\u0020\u007f]/.test(raw)) return '';
        try {
            const url = new URL(raw, location.origin);
            if (!['http:', 'https:'].includes(url.protocol) || url.origin !== location.origin || url.username || url.password) return '';
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
        const status = profileRoot.querySelector('[data-spux-pref-status]');
        if (!status) return;
        status.textContent = '';
        setTimeout(() => { status.textContent = message; }, 20);
    };

    const readPrefs = () => {
        try {
            const parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) return {};
            const clean = {};
            allowedPrefs.forEach((key) => { if (parsed[key] === true || parsed[key] === false) clean[key] = parsed[key]; });
            return clean;
        } catch (_) { return {}; }
    };
    const writePrefs = (prefs) => {
        const clean = {};
        allowedPrefs.forEach((key) => { if (prefs[key] === true || prefs[key] === false) clean[key] = prefs[key]; });
        try { localStorage.setItem(storageKey, JSON.stringify(clean)); } catch (_) {}
    };
    const applyPrefs = (prefs) => {
        allowedPrefs.forEach((key) => {
            root.classList.toggle(`spux-pref-${key}`, prefs[key] === true);
            profileRoot.querySelectorAll(`[data-spux-pref="${key}"]`).forEach((button) => {
                button.setAttribute('aria-pressed', prefs[key] === true ? 'true' : 'false');
            });
        });
        if (prefs['low-data'] === true) {
            profileRoot.querySelectorAll('video,audio').forEach((media) => {
                media.autoplay = false;
                media.preload = 'none';
                try { media.pause(); } catch (_) {}
            });
            profileRoot.querySelectorAll('img:not([fetchpriority="high"])').forEach((image) => {
                image.loading = 'lazy';
                image.decoding = 'async';
            });
        }
    };

    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const initialPrefs = readPrefs();
    if (connection && connection.saveData === true && initialPrefs['low-data'] !== false) initialPrefs['low-data'] = true;

    const toolbar = element('section', 'spux-experience-toolbar');
    toolbar.setAttribute('aria-label', t('toolbarLabel', 'Reading and accessibility preferences'));
    const controls = element('div', 'spux-experience-toolbar__controls');
    const labels = {
        reading: t('readingMode', 'Reading mode'),
        'large-text': t('largerText', 'Larger text'),
        contrast: t('highContrast', 'High contrast'),
        spacing: t('moreSpacing', 'More spacing'),
        simple: t('simplifiedView', 'Simplified view'),
        'low-data': t('lowData', 'Low data'),
    };
    Object.entries(labels).forEach(([key, label]) => {
        const button = element('button', 'spux-pref-toggle', label);
        button.type = 'button';
        button.dataset.spuxPref = key;
        button.setAttribute('aria-pressed', 'false');
        controls.appendChild(button);
    });
    const reset = element('button', 'spux-pref-reset', t('reset', 'Reset'));
    reset.type = 'button';
    reset.dataset.spuxPrefReset = '';
    controls.appendChild(reset);
    toolbar.appendChild(controls);

    if (payload.owner_tools === true) {
        const preview = element('details', 'spux-privacy-preview');
        const summary = element('summary', '', t('privacyPreview', 'Privacy preview simulator'));
        const links = element('div', 'spux-privacy-preview__links');
        const previewLabels = {
            public: t('publicVisitor', 'Public visitor'),
            member: t('loggedInMember', 'Logged-in member'),
            contact: t('contactPresentation', 'Contact presentation'),
            search: t('searchPresentation', 'Search-result presentation'),
            social: t('socialPresentation', 'Social-preview presentation'),
            mobile: t('mobilePresentation', 'Mobile presentation'),
            desktop: t('desktopPresentation', 'Desktop presentation'),
        };
        Object.entries(payload.preview_links || {}).forEach(([mode, value]) => {
            const url = safeUrl(value);
            if (!url || !previewLabels[mode]) return;
            const link = element('a', '', previewLabels[mode]);
            link.href = url;
            links.appendChild(link);
        });
        preview.append(summary, links, element('p', '', t('previewDisclaimer', 'Presentation simulation only; it never grants another role or bypasses authorization.')));
        toolbar.appendChild(preview);

        const quality = payload.quality || {};
        if (Number.isFinite(Number(quality.score))) {
            const details = element('details', 'spux-quality-score');
            details.appendChild(element('summary', '', `${t('qualityLabel', 'Public Experience Quality')}: ${Number(quality.score)}%`));
            const list = element('ul');
            Object.entries(quality.checks || {}).forEach(([key, pass]) => {
                const item = element('li', '', `${key.replaceAll('_', ' ')}: ${pass === true ? t('passLabel', 'Pass') : t('reviewLabel', 'Review')}`);
                item.dataset.status = pass === true ? 'pass' : 'review';
                list.appendChild(item);
            });
            details.append(list, element('p', '', t('qualityDisclaimer', 'Internal quality aid only; it has no public ranking, donation, verification, or visibility effect.')));
            toolbar.appendChild(details);
        }
    }
    const status = element('p', 'spux-sr-only');
    status.dataset.spuxPrefStatus = '';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    toolbar.appendChild(status);

    const hero = profileRoot.querySelector('.spux-hero');
    if (hero) hero.insertAdjacentElement('afterend', toolbar);

    const trust = payload.trust || {};
    if (hero && (trust.label || trust.source_label)) {
        const capsule = element('aside', 'spux-trust-capsule');
        capsule.setAttribute('aria-label', t('trustAriaLabel', 'Profile trust information'));
        if (trust.label) capsule.appendChild(element('strong', '', String(trust.label)));
        if (trust.source_label) capsule.appendChild(element('span', '', String(trust.source_label)));
        if (trust.verified_at) {
            const date = new Date(trust.verified_at);
            if (!Number.isNaN(date.getTime())) {
                const time = element('time', '', `${t('verifiedPrefix', 'Verified')}: ${date.toLocaleDateString()}`);
                time.dateTime = trust.verified_at;
                capsule.appendChild(time);
            }
        }
        if (trust.freshness) capsule.appendChild(element('span', 'spux-trust-capsule__freshness', String(trust.freshness)));
        if (['corrected', 'retracted', 'archived'].includes(trust.correction_state)) {
            capsule.appendChild(element('span', `spux-badge spux-badge--${trust.correction_state}`, trust.correction_state));
        }
        toolbar.insertAdjacentElement('afterend', capsule);
    }

    const actions = profileRoot.querySelector('.spux-hero .spux-actions');
    if (actions) {
        if (!actions.id) actions.id = 'spux-profile-actions';
        const toggle = element('button', 'spux-button spux-button--secondary spux-action-sheet-toggle', t('actionsLabel', 'Profile actions'));
        toggle.type = 'button';
        toggle.dataset.spuxActionSheetToggle = '';
        toggle.setAttribute('aria-controls', actions.id);
        toggle.setAttribute('aria-expanded', 'false');
        actions.parentNode?.insertBefore(toggle, actions);

        const shareUrl = safeUrl(profile.url);
        if (shareUrl && profile.name) {
            const share = element('details', 'spux-share-studio');
            const shareSummary = element('summary', 'spux-button spux-button--secondary', t('shareStudio', 'Share Studio'));
            const panel = element('div', 'spux-share-studio__panel');
            const copy = element('button', 'spux-button spux-button--secondary', t('copyCleanLink', 'Copy clean link'));
            copy.type = 'button';
            copy.dataset.spuxCopyUrl = shareUrl;
            const print = element('button', 'spux-button spux-button--secondary', t('printProfileCard', 'Print profile card'));
            print.type = 'button';
            print.dataset.spuxPrintProfile = '';
            const urlText = element('p', 'spux-share-studio__url', shareUrl);
            urlText.dir = 'ltr';
            const snapshot = element('article', 'spux-snapshot-card');
            snapshot.dataset.spuxSnapshotCard = '';
            const avatar = safeUrl(profile.avatar);
            if (avatar) {
                const image = document.createElement('img');
                image.src = avatar;
                image.alt = '';
                image.width = 88;
                image.height = 88;
                image.loading = 'lazy';
                snapshot.appendChild(image);
            }
            const body = element('div');
            body.appendChild(element('h3', '', String(profile.name)));
            if (profile.verified === true) body.appendChild(element('p', 'spux-badge', String(profile.role_label || t('verifiedPrefix', 'Verified'))));
            if (profile.headline) body.appendChild(element('p', '', String(profile.headline)));
            const place = [profile.city, profile.country].filter(Boolean).join(', ');
            if (place) body.appendChild(element('p', '', place));
            const clean = element('p', '', shareUrl);
            clean.dir = 'ltr';
            body.appendChild(clean);
            snapshot.appendChild(body);
            panel.append(copy, print, urlText, snapshot);
            share.append(shareSummary, panel);
            actions.appendChild(share);
        }
    }

    const main = profileRoot.querySelector('.spux-main');
    if (main && payload.section === 'overview') {
        const fresh = payload.freshness || {};
        if (fresh.updated_at) {
            const date = new Date(fresh.updated_at);
            if (!Number.isNaN(date.getTime())) {
                const p = element('p', 'spux-content-freshness');
                const time = element('time', '', fresh.label || `${t('publicInformationUpdated', 'Public information updated')} ${date.toLocaleDateString()}`);
                time.dateTime = fresh.updated_at;
                p.appendChild(time);
                main.appendChild(p);
            }
        }

        const translations = Array.isArray(payload.translations) ? payload.translations : [];
        if (translations.length) {
            const section = element('section', 'spux-card spux-translation-panel');
            section.dataset.spuxTranslations = '';
            section.appendChild(element('h2', '', t('translations', 'Translations')));
            const switcher = element('div', 'spux-translation-panel__switcher');
            switcher.setAttribute('role', 'group');
            translations.forEach((row) => {
                const button = element('button', '', String(row.label || row.code));
                button.type = 'button';
                button.dataset.spuxTranslationTarget = String(row.code || '');
                button.setAttribute('aria-pressed', 'false');
                switcher.appendChild(button);
            });
            if (translations.length >= 2) {
                const bilingual = element('button', '', t('sideBySide', 'Side by side'));
                bilingual.type = 'button';
                bilingual.dataset.spuxBilingualToggle = '';
                bilingual.setAttribute('aria-pressed', 'false');
                switcher.appendChild(bilingual);
            }
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
            section.append(switcher, content, element('p', 'spux-translation-note', t('translationNote', 'Translations are provider-supplied presentation; the original profile remains authoritative.')));
            main.appendChild(section);
        }

        const relationships = Array.isArray(payload.relationships) ? payload.relationships : [];
        if (relationships.length) {
            const section = element('section', 'spux-card spux-relationship-explorer');
            section.appendChild(element('h2', '', t('knowledgeRelationships', 'Knowledge Relationships')));
            const list = element('ul');
            relationships.forEach((row) => {
                const url = safeUrl(row.url);
                if (!url) return;
                const item = element('li');
                item.dataset.spuxRelationType = String(row.type || 'related');
                const link = element('a');
                link.href = url;
                link.appendChild(element('strong', '', String(row.label || t('relatedItem', 'Related item'))));
                item.appendChild(link);
                if (row.relationship) item.appendChild(element('span', '', String(row.relationship)));
                list.appendChild(item);
            });
            section.appendChild(list);
            main.appendChild(section);
        }

        const citations = Array.isArray(payload.citations) ? payload.citations : [];
        if (citations.length) {
            const details = element('details', 'spux-card spux-citation-drawer');
            details.appendChild(element('summary', '', t('referencesCitations', 'References and citations')));
            const list = element('ol');
            citations.forEach((row) => {
                const url = safeUrl(row.url);
                if (!url) return;
                const item = element('li');
                const link = element('a', '', String(row.title || t('reference', 'Reference')));
                link.href = url;
                item.appendChild(link);
                if (row.source) item.appendChild(document.createTextNode(` — ${row.source}`));
                list.appendChild(item);
            });
            details.appendChild(list);
            main.appendChild(details);
        }
    }

    const timelineMeta = Array.isArray(payload.timeline) ? payload.timeline : [];
    const metaByUrl = new Map(timelineMeta.map((row) => [canonicalHref(row.url), row]).filter(([url]) => url !== ''));
    const timelineCards = [...profileRoot.querySelectorAll('.spux-timeline-card')];
    timelineCards.forEach((card) => {
        card.dataset.spuxTimelineCard = '';
        const link = card.querySelector('h3 a[href]');
        const meta = link ? metaByUrl.get(canonicalHref(link.href)) : null;
        if (!meta) return;
        if (meta.source_verified === true && !card.querySelector('[data-spux-source-verified]')) {
            const provenance = element('div', 'spux-provenance');
            provenance.setAttribute('aria-label', t('sourceVerified', 'Verified source projection'));
            const badge = element('span', 'spux-badge spux-badge--provenance', t('sourceVerified', 'Verified source projection'));
            badge.dataset.spuxSourceVerified = '';
            provenance.appendChild(badge);
            card.querySelector('.spux-card__meta')?.after(provenance);
        }
        if (meta.updated_at && !card.querySelector('.spux-item-freshness')) {
            const date = new Date(meta.updated_at);
            if (!Number.isNaN(date.getTime())) {
                const wrap = element('span', 'spux-item-freshness');
                const time = element('time', '', `${t('updatedLabel', 'Updated')} ${date.toLocaleDateString()}`);
                time.dateTime = meta.updated_at;
                wrap.appendChild(time);
                card.querySelector('.spux-card__meta')?.appendChild(wrap);
            }
        }
    });

    if (timelineCards.length) {
        const yearMap = new Map();
        timelineCards.forEach((card) => {
            const datetime = card.querySelector('time[datetime]')?.getAttribute('datetime') || '';
            const match = datetime.match(/^(19|20)\d{2}/);
            if (!match) return;
            card.dataset.spuxTimelineYear = match[0];
            yearMap.set(match[0], (yearMap.get(match[0]) || 0) + 1);
        });
        const years = [...yearMap.keys()].sort((a, b) => Number(b) - Number(a));
        const heading = profileRoot.querySelector('.spux-section-heading');
        if (years.length && heading) {
            const navigator = element('div', 'spux-time-navigator');
            navigator.dataset.spuxTimeNavigator = '';
            const nav = element('nav', 'spux-time-navigator__nav');
            nav.setAttribute('aria-label', t('timelineYears', 'Timeline years'));
            const makeButton = (year, label) => {
                const button = element('button', 'spux-time-navigator__item', label);
                button.type = 'button';
                button.dataset.spuxYear = year;
                button.setAttribute('aria-pressed', year === '' ? 'true' : 'false');
                return button;
            };
            nav.appendChild(makeButton('', t('allYears', 'All years')));
            years.forEach((year) => nav.appendChild(makeButton(year, year)));
            const era = element('details', 'spux-era-summary');
            era.appendChild(element('summary', '', t('eraSummary', 'Timeline era summary')));
            const list = element('ul');
            years.forEach((year) => list.appendChild(element('li', '', `${year}: ${yearMap.get(year)} ${t('itemsOnThisPage', 'public items on this page')}`)));
            era.appendChild(list);
            const navStatus = element('p', 'spux-sr-only');
            navStatus.setAttribute('role', 'status');
            navStatus.setAttribute('aria-live', 'polite');
            navigator.append(nav, era, navStatus);
            heading.after(navigator);
            nav.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const button = target?.closest('[data-spux-year]');
                if (!button) return;
                const selected = button.dataset.spuxYear || '';
                nav.querySelectorAll('[data-spux-year]').forEach((candidate) => candidate.setAttribute('aria-pressed', candidate === button ? 'true' : 'false'));
                let shown = 0;
                timelineCards.forEach((card) => {
                    const visible = selected === '' || card.dataset.spuxTimelineYear === selected;
                    card.hidden = !visible;
                    if (visible) shown += 1;
                });
                navStatus.textContent = selected
                    ? `${shown} ${t('itemsShownForYear', 'public items shown for')} ${selected}.`
                    : `${shown} ${t('itemsShownOnPage', 'public items shown on this page.')}`;
            });
        }
    }

    applyPrefs(initialPrefs);

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target || !profileRoot.contains(target)) return;
        const pref = target.closest('[data-spux-pref]');
        if (pref) {
            const key = pref.dataset.spuxPref || '';
            if (!allowedPrefs.has(key)) return;
            const prefs = readPrefs();
            prefs[key] = prefs[key] !== true;
            writePrefs(prefs);
            applyPrefs(prefs);
            announce(t('preferenceUpdated', 'Display preference updated.'));
            return;
        }
        if (target.closest('[data-spux-pref-reset]')) {
            writePrefs({});
            applyPrefs({});
            announce(t('preferencesReset', 'Display preferences reset.'));
            return;
        }
        const toggle = target.closest('[data-spux-action-sheet-toggle]');
        if (toggle && actions) {
            const open = !actions.classList.contains('is-open');
            actions.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) setTimeout(() => actions.querySelector('a[href],button:not([disabled]),summary')?.focus(), 0);
            else toggle.focus();
            return;
        }
        const copy = target.closest('[data-spux-copy-url]');
        if (copy) {
            const url = safeUrl(copy.dataset.spuxCopyUrl);
            if (!url) return;
            const done = () => announce(t('linkCopied', 'Link copied.'));
            const failed = () => announce(t('copyFailed', 'Unable to copy the clean profile link.'));
            if (navigator.clipboard?.writeText && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done).catch(failed);
            } else {
                const field = document.createElement('textarea');
                field.value = url;
                field.readOnly = true;
                field.style.position = 'fixed';
                field.style.insetInlineStart = '-9999px';
                document.body.appendChild(field);
                field.select();
                let copied = false;
                try { copied = document.execCommand('copy'); } catch (_) { copied = false; }
                field.remove();
                copied ? done() : failed();
            }
            return;
        }
        if (target.closest('[data-spux-print-profile]')) {
            root.classList.add('spux-print-snapshot');
            window.print();
            setTimeout(() => root.classList.remove('spux-print-snapshot'), 250);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !actions?.classList.contains('is-open')) return;
        actions.classList.remove('is-open');
        const toggle = profileRoot.querySelector('[data-spux-action-sheet-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });

    const translationRoot = profileRoot.querySelector('[data-spux-translations]');
    if (translationRoot) {
        const buttons = [...translationRoot.querySelectorAll('[data-spux-translation-target]')];
        const panels = [...translationRoot.querySelectorAll('[data-spux-translation]')];
        const bilingual = translationRoot.querySelector('[data-spux-bilingual-toggle]');
        let primaryCode = buttons[0]?.dataset.spuxTranslationTarget || '';
        const showSingle = (code) => {
            primaryCode = code || primaryCode;
            translationRoot.classList.remove('is-bilingual');
            bilingual?.setAttribute('aria-pressed', 'false');
            buttons.forEach((button) => button.setAttribute('aria-pressed', button.dataset.spuxTranslationTarget === primaryCode ? 'true' : 'false'));
            panels.forEach((panel) => { panel.hidden = panel.dataset.spuxTranslation !== primaryCode; });
        };
        buttons.forEach((button) => button.addEventListener('click', () => showSingle(button.dataset.spuxTranslationTarget || '')));
        bilingual?.addEventListener('click', () => {
            const enabled = !translationRoot.classList.contains('is-bilingual');
            if (!enabled) {
                showSingle(primaryCode);
                return;
            }
            const secondary = panels.find((panel) => panel.dataset.spuxTranslation && panel.dataset.spuxTranslation !== primaryCode)?.dataset.spuxTranslation || '';
            if (!primaryCode || !secondary) return;
            translationRoot.classList.add('is-bilingual');
            bilingual.setAttribute('aria-pressed', 'true');
            buttons.forEach((button) => button.setAttribute('aria-pressed', 'false'));
            panels.forEach((panel) => {
                const code = panel.dataset.spuxTranslation || '';
                panel.hidden = code !== primaryCode && code !== secondary;
            });
        });
        if (primaryCode) showSingle(primaryCode);
    }

    const saveScroll = () => {
        try { sessionStorage.setItem(scrollKey, JSON.stringify({ y: Math.max(0, Math.round(scrollY)), at: Date.now() })); } catch (_) {}
    };
    addEventListener('pagehide', saveScroll, { capture: true });
    addEventListener('pageshow', (event) => {
        const nav = performance.getEntriesByType?.('navigation')?.[0];
        if (!(event.persisted || nav?.type === 'back_forward')) return;
        try {
            const state = JSON.parse(sessionStorage.getItem(scrollKey) || '{}');
            if (Number.isFinite(state.y) && Date.now() - Number(state.at || 0) < 3600000) {
                requestAnimationFrame(() => scrollTo({ top: state.y, left: 0, behavior: 'auto' }));
            }
        } catch (_) {}
    });

    if (payload.owner_tools === true) {
        const issues = [];
        profileRoot.querySelectorAll('img').forEach((image) => { if (!image.hasAttribute('alt')) issues.push('image_missing_alt'); });
        profileRoot.querySelectorAll('a[href],button,summary').forEach((control) => {
            const box = control.getBoundingClientRect();
            if (box.width > 0 && box.height > 0 && (box.width < 24 || box.height < 24)) issues.push('small_interactive_target');
        });
        if (profileRoot.scrollWidth > profileRoot.clientWidth + 2) issues.push('horizontal_overflow');
        const unique = [...new Set(issues)];
        dispatchEvent(new CustomEvent('spux:visual-integrity', { detail: { status: unique.length ? 'review' : 'pass', issues: unique, issueCount: unique.length } }));
        profileRoot.dataset.spuxVisualIntegrity = unique.length ? 'review' : 'pass';
    }
})();
