<?php
/** @var array<string,mixed> $profile */
$details = (array) ($profile['founder_details'] ?? []);
$professional = (array) ($profile['professional'] ?? []);
?>
<section class="spux-section-stack" aria-labelledby="spux-section-title">
    <article class="spux-card spux-prose">
        <h2 id="spux-section-title"><?php esc_html_e('About', 'sabri-public-experience'); ?></h2>
        <?php if (! empty($profile['bio'])) : ?>
            <div><?php echo wp_kses_post(wpautop((string) $profile['bio'])); ?></div>
        <?php endif; ?>
    </article>

    <?php if (! empty($details['methodology'])) : ?>
        <article class="spux-card spux-prose">
            <h3><?php esc_html_e('Professional Methodology', 'sabri-public-experience'); ?></h3>
            <p><?php echo esc_html((string) $details['methodology']); ?></p>
        </article>
    <?php endif; ?>

    <?php if (! empty($details['experience'])) : ?>
        <article class="spux-card spux-prose">
            <h3><?php esc_html_e('Experience', 'sabri-public-experience'); ?></h3>
            <p><?php echo esc_html((string) $details['experience']); ?></p>
        </article>
    <?php endif; ?>

    <?php if ($professional !== [] && ! empty($professional['books_studied'])) : ?>
        <article class="spux-card spux-prose">
            <h3><?php esc_html_e('Studied Books', 'sabri-public-experience'); ?></h3>
            <ul class="spux-publication-list">
                <?php foreach ((array) $professional['books_studied'] as $book) : ?>
                    <li><?php echo esc_html((string) $book); ?></li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

    <?php if (empty($profile['bio']) && empty($details['methodology']) && empty($details['experience']) && empty($professional['books_studied'])) : ?>
        <div class="spux-state spux-state--empty" role="status">
            <p><?php esc_html_e('No additional public biography is available yet.', 'sabri-public-experience'); ?></p>
        </div>
    <?php endif; ?>
</section>
