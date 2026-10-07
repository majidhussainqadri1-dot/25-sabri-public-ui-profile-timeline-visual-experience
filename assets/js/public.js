(() => {
    'use strict';

    const settings = window.sabriPublicExperience || {};

    const announce = (button, message) => {
        const describedBy = button.getAttribute('aria-describedby');
        const status = describedBy ? document.getElementById(describedBy) : null;
        if (status) {
            status.textContent = '';
            window.setTimeout(() => {
                status.textContent = message;
            }, 20);
        }
    };

    const legacyCopy = (text) => {
        const field = document.createElement('textarea');
        field.value = text;
        field.setAttribute('readonly', '');
        field.style.position = 'fixed';
        field.style.insetInlineStart = '-9999px';
        document.body.appendChild(field);
        field.select();
        field.setSelectionRange(0, field.value.length);
        let copied = false;
        try {
            copied = document.execCommand('copy');
        } finally {
            field.remove();
        }
        return copied;
    };

    const copyLink = async (url) => {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function' && window.isSecureContext) {
            await navigator.clipboard.writeText(url);
            return true;
        }
        return legacyCopy(url);
    };


    const profileRoot = document.querySelector('[data-spux-profile-class]');
    if (profileRoot && 'scrollRestoration' in window.history) {
        // File 25 §45 requires browser-Back scroll restoration while filters and
        // pagination remain URL-addressable. Keep state local to this tab only.
        const key = 'spux-scroll:' + window.location.pathname + window.location.search;
        try {
            window.history.scrollRestoration = 'manual';
            window.addEventListener('pagehide', () => {
                window.sessionStorage.setItem(key, String(Math.max(0, Math.round(window.scrollY))));
            });
            const navigation = window.performance && typeof window.performance.getEntriesByType === 'function'
                ? window.performance.getEntriesByType('navigation')[0]
                : null;
            if (navigation && navigation.type === 'back_forward') {
                const stored = Number.parseInt(window.sessionStorage.getItem(key) || '', 10);
                if (Number.isFinite(stored) && stored >= 0) {
                    window.requestAnimationFrame(() => window.scrollTo(0, stored));
                }
            }
        } catch (error) {
            // Storage/privacy restrictions must never break public profile use.
            window.history.scrollRestoration = 'auto';
        }
    }

    const connectionStatus = document.querySelector('[data-spux-connection-status]');
    if (connectionStatus) {
        let restoredTimer = null;
        const renderConnection = () => {
            if (! navigator.onLine) {
                if (restoredTimer !== null) {
                    window.clearTimeout(restoredTimer);
                    restoredTimer = null;
                }
                connectionStatus.textContent = settings.offline
                    || 'You are offline. The page remains available, but new network actions may not complete.';
                connectionStatus.hidden = false;
                connectionStatus.setAttribute('data-state', 'offline');
                return;
            }

            if (connectionStatus.getAttribute('data-state') === 'offline') {
                connectionStatus.textContent = settings.onlineRestored || 'Connection restored.';
                connectionStatus.hidden = false;
                connectionStatus.setAttribute('data-state', 'online');
                restoredTimer = window.setTimeout(() => {
                    connectionStatus.hidden = true;
                    connectionStatus.removeAttribute('data-state');
                }, 3000);
                return;
            }
            connectionStatus.hidden = true;
        };
        window.addEventListener('offline', renderConnection);
        window.addEventListener('online', renderConnection);
        renderConnection();
    }

    document.querySelectorAll('[data-spux-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (button.disabled) {
                return;
            }

            const canonical = button.getAttribute('data-url')
                || document.querySelector('link[rel="canonical"]')?.href
                || window.location.href.split('#')[0];
            const shareData = {
                title: button.getAttribute('data-title') || document.title,
                url: canonical,
            };

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.removeAttribute('data-share-error');

            try {
                if (typeof navigator.share === 'function') {
                    await navigator.share(shareData);
                    announce(button, settings.shareSucceeded || 'Profile shared.');
                    return;
                }

                if (await copyLink(shareData.url)) {
                    announce(button, settings.linkCopied || 'Link copied.');
                    return;
                }

                throw new Error('copy_unavailable');
            } catch (error) {
                if (! error || error.name !== 'AbortError') {
                    button.setAttribute('data-share-error', 'true');
                    announce(button, settings.shareFailed || 'Unable to share this profile.');
                }
            } finally {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    });
})();
