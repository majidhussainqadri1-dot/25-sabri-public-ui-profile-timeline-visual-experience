<?php
/** @var array<string,mixed> $profile */
$details = (array) ($profile['founder_details'] ?? []);
$publications = array_values(array_filter((array) ($details['publications'] ?? []), 'is_string'));
?>
<section class="spux-section-stack" aria-labelledby="spux-section-title">
    <div class="spux-card spux-prose">
        <p class="spux-eyebrow"><?php esc_html_e('Founder Knowledge Record', 'sabri-public-experience'); ?></p>
        <h2 id="spux-section-title"><?php esc_html_e('Books and Research', 'sabri-public-experience'); ?></h2>
        <?php if (! empty($details['research'])) : ?>
            <h3><?php esc_html_e('Research and Knowledge Areas', 'sabri-public-experience'); ?></h3>
            <p><?php echo esc_html((string) $details['research']); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($publications !== []) : ?>
        <article class="spux-card spux-prose">
            <h3><?php esc_html_e('Books and Publications', 'sabri-public-experience'); ?></h3>
            <ol class="spux-publication-list spux-publication-list--numbered">
                <?php foreach ($publications as $publication) : ?>
                    <li><?php echo esc_html($publication); ?></li>
                <?php endforeach; ?>
            </ol>
        </article>
    <?php else : ?>
        <div class="spux-state spux-state--empty" role="status">
            <p><?php esc_html_e('No approved publication list is currently available.', 'sabri-public-experience'); ?></p>
        </div>
    <?php endif; ?>

    <aside class="spux-notice spux-notice--warning">
        <p><?php esc_html_e('This section is an author-centered public index. Full books, lessons, encyclopedia entries, videos, and PDFs remain owned by their native platform modules and will link here only through accepted providers.', 'sabri-public-experience'); ?></p>
    </aside>
</section>
