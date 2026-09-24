<?php
/** @var array<string,mixed> $profile */
$display_name = trim((string) ($profile['display_name'] ?? ''));
$initials = '';
foreach (array_slice(preg_split('/\s+/u', $display_name) ?: [], 0, 2) as $word) {
    if ($word === '') {
        continue;
    }
    $initials .= function_exists('mb_substr') ? mb_substr($word, 0, 1) : substr($word, 0, 1);
}
$initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials) : strtoupper($initials);
$location = implode(', ', array_filter([(string) ($profile['city'] ?? ''), (string) ($profile['country'] ?? '')]));
if ($location === '') {
    $location = trim((string) ($profile['location_text'] ?? ''));
}
$canonical_url = \Sabri\PublicExperience\Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false);
$qr_image_url = '';
if ($canonical_url !== '' && function_exists('apply_filters')) {
    $qr_candidate = apply_filters('sabri_public_experience/profile_qr_image_url', '', $canonical_url, $profile);
    $qr_image_url = \Sabri\PublicExperience\Public_URL::sanitize_same_site($qr_candidate, false);
}
?>
<header class="spux-hero">
    <div class="spux-hero__cover" aria-hidden="true">
        <?php if (! empty($profile['cover_url'])) : ?>
            <img src="<?php echo esc_url((string) $profile['cover_url']); ?>" alt="" width="1600" height="480" fetchpriority="high">
        <?php endif; ?>
    </div>
    <div class="spux-hero__body">
        <?php if (! empty($profile['avatar_url'])) : ?>
            <img class="spux-avatar" src="<?php echo esc_url((string) $profile['avatar_url']); ?>" alt="<?php echo esc_attr(sprintf(__('%s profile photograph', 'sabri-public-experience'), $display_name)); ?>" width="160" height="160">
        <?php else : ?>
            <span class="spux-avatar spux-avatar--initials" aria-hidden="true"><?php echo esc_html($initials ?: 'SH'); ?></span>
        <?php endif; ?>
        <div class="spux-identity">
            <div class="spux-identity__title-row">
                <h1><?php echo esc_html($display_name); ?></h1>
                <?php if (! empty($profile['verified'])) : ?>
                    <span class="spux-badge"><?php echo esc_html((string) $profile['role_label']); ?></span>
                <?php elseif (! empty($profile['role_label'])) : ?>
                    <span class="spux-role-label"><?php echo esc_html((string) $profile['role_label']); ?></span>
                <?php endif; ?>
            </div>
            <?php if (! empty($profile['headline'])) : ?><p class="spux-headline"><?php echo esc_html((string) $profile['headline']); ?></p><?php endif; ?>
            <?php if ($location !== '') : ?><p class="spux-location"><?php echo esc_html($location); ?></p><?php endif; ?>
        </div>
        <div class="spux-actions" aria-label="<?php esc_attr_e('Profile actions', 'sabri-public-experience'); ?>">
            <?php foreach ((array) ($profile['contacts'] ?? []) as $type => $value) : ?>
                <?php
                $digits = preg_replace('/[^0-9+]/', '', (string) $value) ?? '';
                $href = match ($type) {
                    'phone' => $digits !== '' ? 'tel:' . $digits : '',
                    'whatsapp' => preg_replace('/[^0-9]/', '', $digits) !== '' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $digits) : '',
                    default => '',
                };
                $label = $type === 'whatsapp' ? __('WhatsApp', 'sabri-public-experience') : __('Call', 'sabri-public-experience');
                if ($href === '') { continue; }
                ?>
                <a class="spux-button spux-button--secondary" href="<?php echo esc_url($href); ?>" rel="noopener noreferrer"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
            <?php echo \Sabri\PublicExperience\Plan_Completion::render_profile_actions($profile); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <button class="spux-button spux-share" type="button" data-spux-share data-title="<?php echo esc_attr($display_name); ?>" data-url="<?php echo esc_url($canonical_url); ?>" aria-describedby="spux-share-status"><?php esc_html_e('Share', 'sabri-public-experience'); ?></button>
            <span id="spux-share-status" class="spux-sr-only" role="status" aria-live="polite"></span>
            <?php if ($qr_image_url !== '') : ?>
                <details class="spux-profile-qr">
                    <summary class="spux-button spux-button--secondary"><?php esc_html_e('QR', 'sabri-public-experience'); ?></summary>
                    <img class="spux-profile-qr__image" src="<?php echo esc_url($qr_image_url); ?>" alt="<?php echo esc_attr(sprintf(__('QR code for %s public profile', 'sabri-public-experience'), $display_name)); ?>" width="160" height="160" loading="lazy">
                </details>
            <?php endif; ?>
        </div>
    </div>
</header>
