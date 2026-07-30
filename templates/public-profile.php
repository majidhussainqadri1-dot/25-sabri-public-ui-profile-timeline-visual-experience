<?php
/**
 * Public profile template.
 *
 * @var array<string,mixed> $profile
 * @var array<string,mixed> $context
 * @var array<string,mixed> $timeline
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$profile = (array) ($GLOBALS['sabri_public_experience_profile'] ?? []);
$context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
$timeline = (array) ($GLOBALS['sabri_public_experience_timeline'] ?? []);
$section = sanitize_key((string) ($context['section'] ?? 'overview')) ?: 'overview';
$sections = (array) ($profile['section_labels'] ?? []);
?>
<main id="sabri-main-content" class="spux-profile" tabindex="-1">
    <div class="spux-container">
        <?php if (is_404() || $profile === []) : ?>
            <section class="spux-state spux-state--error" role="alert">
                <h1><?php esc_html_e('Profile unavailable', 'sabri-public-experience'); ?></h1>
                <p><?php esc_html_e('This profile is private, unavailable, or no longer published.', 'sabri-public-experience'); ?></p>
                <a class="spux-button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Return Home', 'sabri-public-experience'); ?></a>
            </section>
        <?php else : ?>
            <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/profile-hero.php'; ?>

            <?php if (count($sections) > 1) : ?>
                <nav class="spux-tabs" aria-label="<?php esc_attr_e('Profile sections', 'sabri-public-experience'); ?>">
                    <?php foreach ($sections as $slug => $label) : ?>
                        <?php
                        $slug = sanitize_key((string) $slug);
                        $active = $section === $slug;
                        $base = ($profile['class'] ?? '') === 'founder'
                            ? home_url('/founder/')
                            : (($profile['class'] ?? '') === 'doctor'
                                ? home_url('/doctors/' . rawurlencode((string) $profile['slug']) . '/')
                                : home_url('/profile/' . rawurlencode((string) $profile['slug']) . '/'));
                        $url = $slug === 'overview' ? $base : trailingslashit($base . $slug);
                        ?>
                        <a class="spux-tabs__item<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                            <?php echo esc_html((string) $label); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>

            <div class="spux-layout">
                <section class="spux-main" aria-labelledby="spux-section-title">
                    <?php if ($section === 'timeline') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/timeline.php'; ?>
                    <?php elseif (in_array($section, ['clinic', 'clinic-contact'], true)) : ?>
                        <div class="spux-card spux-prose">
                            <h2 id="spux-section-title"><?php echo esc_html((string) ($sections[$section] ?? __('Clinic and Contact', 'sabri-public-experience'))); ?></h2>
                            <?php if (! empty($profile['contacts'])) : ?>
                                <p><?php esc_html_e('Use the approved professional contact methods below.', 'sabri-public-experience'); ?></p>
                                <div class="spux-actions">
                                    <?php foreach ((array) $profile['contacts'] as $type => $value) : ?>
                                        <?php
                                        $digits = preg_replace('/[^0-9+]/', '', (string) $value) ?? '';
                                        $href = $type === 'whatsapp'
                                            ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $digits)
                                            : 'tel:' . $digits;
                                        $label = $type === 'whatsapp'
                                            ? __('WhatsApp', 'sabri-public-experience')
                                            : __('Call', 'sabri-public-experience');
                                        if ($digits === '' || $href === 'tel:' || $href === 'https://wa.me/') {
                                            continue;
                                        }
                                        ?>
                                        <a class="spux-button spux-button--secondary" href="<?php echo esc_url($href); ?>" rel="noopener noreferrer">
                                            <?php echo esc_html($label); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="spux-state spux-state--empty" role="status">
                                    <p><?php esc_html_e('No approved public contact method is currently available.', 'sabri-public-experience'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($section === 'about') : ?>
                        <div class="spux-card spux-prose">
                            <h2 id="spux-section-title"><?php esc_html_e('About', 'sabri-public-experience'); ?></h2>
                            <?php if (! empty($profile['bio'])) : ?>
                                <div><?php echo wp_kses_post(wpautop((string) $profile['bio'])); ?></div>
                            <?php else : ?>
                                <div class="spux-state spux-state--empty" role="status">
                                    <p><?php esc_html_e('No public biography is available yet.', 'sabri-public-experience'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="spux-card spux-prose">
                            <h2 id="spux-section-title"><?php esc_html_e('Overview', 'sabri-public-experience'); ?></h2>
                            <?php if (! empty($profile['headline'])) : ?>
                                <p class="spux-overview-headline"><?php echo esc_html((string) $profile['headline']); ?></p>
                            <?php endif; ?>
                            <?php if (! empty($profile['bio'])) : ?>
                                <p><?php echo esc_html(wp_trim_words((string) $profile['bio'], 55)); ?></p>
                                <?php if (isset($sections['about'])) : ?>
                                    <a class="spux-read-more" href="<?php echo esc_url(trailingslashit((string) $profile['canonical_url'] . 'about')); ?>">
                                        <?php esc_html_e('Read full biography', 'sabri-public-experience'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php elseif (empty($profile['headline'])) : ?>
                                <div class="spux-state spux-state--empty" role="status">
                                    <p><?php esc_html_e('No public information is available yet.', 'sabri-public-experience'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
