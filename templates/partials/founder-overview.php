<?php
/** @var array<string,mixed> $profile */
$details = (array) ($profile['founder_details'] ?? []);
$publications = array_values(array_filter((array) ($details['publications'] ?? []), 'is_string'));
?>
<section class="spux-section-stack" aria-labelledby="spux-section-title">
    <div class="spux-card spux-prose spux-card--lead">
        <p class="spux-eyebrow"><?php esc_html_e('Official Founder Profile', 'sabri-public-experience'); ?></p>
        <h2 id="spux-section-title"><?php esc_html_e('Overview', 'sabri-public-experience'); ?></h2>
        <?php if (! empty($profile['bio'])) : ?>
            <p class="spux-lead"><?php echo esc_html((string) $profile['bio']); ?></p>
        <?php elseif (! empty($profile['headline'])) : ?>
            <p class="spux-lead"><?php echo esc_html((string) $profile['headline']); ?></p>
        <?php endif; ?>
    </div>

    <?php if (! empty($details['mission']) || ! empty($details['vision'])) : ?>
        <div class="spux-card-grid spux-card-grid--two">
            <?php if (! empty($details['mission'])) : ?>
                <article class="spux-card spux-prose">
                    <h3><?php esc_html_e('Mission', 'sabri-public-experience'); ?></h3>
                    <p><?php echo esc_html((string) $details['mission']); ?></p>
                </article>
            <?php endif; ?>
            <?php if (! empty($details['vision'])) : ?>
                <article class="spux-card spux-prose">
                    <h3><?php esc_html_e('Vision', 'sabri-public-experience'); ?></h3>
                    <p><?php echo esc_html((string) $details['vision']); ?></p>
                </article>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (! empty($details['objectives'])) : ?>
        <article class="spux-card spux-prose">
            <h3><?php esc_html_e('Objectives', 'sabri-public-experience'); ?></h3>
            <p><?php echo esc_html((string) $details['objectives']); ?></p>
        </article>
    <?php endif; ?>

    <?php if ($publications !== []) : ?>
        <article class="spux-card spux-prose">
            <div class="spux-card__heading-row">
                <h3><?php esc_html_e('Selected Books and Publications', 'sabri-public-experience'); ?></h3>
                <?php if (isset($sections['books-research'])) : ?>
                    <a class="spux-text-link" href="<?php echo esc_url(trailingslashit((string) $profile['canonical_url'] . 'books-research')); ?>">
                        <?php esc_html_e('View all', 'sabri-public-experience'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <ul class="spux-publication-list">
                <?php foreach (array_slice($publications, 0, 4) as $publication) : ?>
                    <li><?php echo esc_html($publication); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

    <aside class="spux-notice spux-notice--trust" aria-label="<?php esc_attr_e('Profile trust notice', 'sabri-public-experience'); ?>">
        <strong><?php esc_html_e('Official identity', 'sabri-public-experience'); ?></strong>
        <p><?php esc_html_e('This page presents the institution-approved Founder identity and public information supplied through the platform’s native profile system. Counts and claims are never fabricated by File 25.', 'sabri-public-experience'); ?></p>
    </aside>
</section>
