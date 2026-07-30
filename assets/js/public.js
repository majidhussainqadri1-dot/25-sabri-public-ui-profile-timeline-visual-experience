(() => {
    'use strict';

    const shareButtons = document.querySelectorAll('[data-spux-share]');

    shareButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const shareData = {
                title: button.getAttribute('data-title') || document.title,
                url: window.location.href,
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                    return;
                }

                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(shareData.url);
                    const previous = button.textContent;
                    button.textContent = window.sabriPublicExperience?.linkCopied || 'Link copied';
                    window.setTimeout(() => {
                        button.textContent = previous;
                    }, 1800);
                }
            } catch (error) {
                // User cancellation is not an application error.
                if (error?.name !== 'AbortError') {
                    button.setAttribute('data-share-error', 'true');
                }
            }
        });
    });
})();
