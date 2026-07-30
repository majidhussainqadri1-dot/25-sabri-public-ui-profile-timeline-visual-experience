<?php
/** @var array<string,mixed> $profile */
$professional = (array) ($profile['professional'] ?? []);
?>
<section class="spux-section-stack" aria-labelledby="spux-section-title">
    <div class="spux-card spux-prose spux-card--lead">
        <p class="spux-eyebrow"><?php esc_html_e('Verified Professional Profile', 'sabri-public-experience'); ?></p>
        <h2 id="spux-section-title"><?php esc_html_e('Overview', 'sabri-public-experience'); ?></h2>
        <?php if (! empty($profile['bio'])) : ?>
            <p class="spux-lead"><?php echo esc_html(wp_trim_words((string) $profile['bio'], 75)); ?></p>
            <?php if (isset($sections['about'])) : ?>
                <a class="spux-text-link" href="<?php echo esc_url(trailingslashit((string) $profile['canonical_url'] . 'about')); ?>">
                    <?php esc_html_e('Read full professional biography', 'sabri-public-experience'); ?>
                </a>
            <?php endif; ?>
        <?php elseif (! empty($profile['headline'])) : ?>
            <p class="spux-lead"><?php echo esc_html((string) $profile['headline']); ?></p>
        <?php endif; ?>
    </div>

    <?php if ($professional !== []) : ?>
        <article class="spux-card spux-prose">
            <h3><?php esc_html_e('Professional Information', 'sabri-public-experience'); ?></h3>
            <dl class="spux-definition-grid">
                <?php if (! empty($professional['qualification'])) : ?>
                    <div><dt><?php esc_html_e('Qualification', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $professional['qualification']); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($professional['institution'])) : ?>
                    <div><dt><?php esc_html_e('Institution', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $professional['institution']); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($professional['council'])) : ?>
                    <div><dt><?php esc_html_e('Reviewing or Licensing Authority', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $professional['council']); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($professional['specialization'])) : ?>
                    <div><dt><?php esc_html_e('Specialization', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $professional['specialization']); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($professional['experience_years'])) : ?>
                    <div><dt><?php esc_html_e('Experience', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html(sprintf(_n('%d year', '%d years', (int) $professional['experience_years'], 'sabri-public-experience'), (int) $professional['experience_years'])); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($professional['languages'])) : ?>
                    <div><dt><?php esc_html_e('Languages', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html(implode(', ', (array) $professional['languages'])); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($professional['consultation_mode'])) : ?>
                    <div><dt><?php esc_html_e('Consultation Modes', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html(implode(', ', (array) $professional['consultation_mode'])); ?></dd></div>
                <?php endif; ?>
            </dl>
        </article>
    <?php endif; ?>

    <aside class="spux-notice spux-notice--trust">
        <strong><?php esc_html_e('Verification scope', 'sabri-public-experience'); ?></strong>
        <p><?php esc_html_e('Institutional verification confirms that submitted identity and professional evidence passed the platform’s review workflow. It is not a guarantee of treatment outcome and does not replace local licensing requirements.', 'sabri-public-experience'); ?></p>
    </aside>
</section>
