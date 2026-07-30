<?php
/** @var array<string,mixed> $profile */
$cover_style = ! empty($profile['cover_url'])
    ? ' style="background-image:url(' . esc_url((string) $profile['cover_url']) . ')"'
    : '';
?>
<header class="spux-hero">
    <div class="spux-hero__cover"<?php echo $cover_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
    <div class="spux-hero__body">
        <img class="spux-avatar" src="<?php echo esc_url((string) $profile['avatar_url']); ?>" alt="<?php echo esc_attr(sprintf(__('%s profile photograph', 'sabri-public-experience'), (string) $profile['display_name'])); ?>" width="160" height="160">
        <div class="spux-identity">
            <div class="spux-identity__title-row">
                <h1><?php echo esc_html((string) $profile['display_name']); ?></h1>
                <?php if (! empty($profile['verified'])) : ?>
                    <span class="spux-badge" aria-label="<?php echo esc_attr((string) $profile['role_label']); ?>">
                        <?php echo esc_html((string) $profile['role_label']); ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (! empty($profile['headline'])) : ?>
                <p class="spux-headline"><?php echo esc_html((string) $profile['headline']); ?></p>
            <?php endif; ?>
            <?php if (! empty($profile['city']) || ! empty($profile['country'])) : ?>
                <p class="spux-location"><?php echo esc_html(implode(', ', array_filter([(string) $profile['city'], (string) $profile['country']]))); ?></p>
            <?php endif; ?>
        </div>
        <div class="spux-actions" aria-label="<?php esc_attr_e('Profile actions', 'sabri-public-experience'); ?>">
            <?php foreach ((array) ($profile['contacts'] ?? []) as $type => $value) : ?>
                <?php
                $href = match ($type) {
                    'phone' => 'tel:' . preg_replace('/[^+0-9]/', '', (string) $value),
                    'whatsapp' => 'https://wa.me/' . preg_replace('/[^0-9]/', '', (string) $value),
                    'email' => 'mailto:' . sanitize_email((string) $value),
                    default => '',
                };
                if ($href === '') {
                    continue;
                }
                ?>
                <a class="spux-button spux-button--secondary" href="<?php echo esc_url($href); ?>" rel="noopener noreferrer">
                    <?php echo esc_html(ucfirst((string) $type)); ?>
                </a>
            <?php endforeach; ?>
            <button class="spux-button spux-share" type="button" data-spux-share data-title="<?php echo esc_attr((string) $profile['display_name']); ?>">
                <?php esc_html_e('Share', 'sabri-public-experience'); ?>
            </button>
        </div>
    </div>
</header>
