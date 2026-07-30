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
