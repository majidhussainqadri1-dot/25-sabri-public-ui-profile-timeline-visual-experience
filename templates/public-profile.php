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
$profile_class = sanitize_key((string) ($profile['class'] ?? 'member'));
?>
<main id="sabri-main-content" class="spux-profile" tabindex="-1" data-spux-profile-class="<?php echo esc_attr($profile_class); ?>" data-spux-section="<?php echo esc_attr($section); ?>">
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
                        $base = $profile_class === 'founder'
                            ? home_url('/founder/')
                            : ($profile_class === 'doctor'
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
                <section class="spux-main">
                    <?php if ($section === 'timeline') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/timeline.php'; ?>
                    <?php elseif ($profile_class === 'founder' && $section === 'overview') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/founder-overview.php'; ?>
                    <?php elseif ($profile_class === 'doctor' && $section === 'overview') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/doctor-overview.php'; ?>
                    <?php elseif ($profile_class === 'founder' && $section === 'books-research') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/books-research.php'; ?>
                    <?php elseif (in_array($section, ['clinic', 'clinic-contact'], true)) : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/clinic-contact.php'; ?>
                    <?php elseif ($section === 'about') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/about.php'; ?>
                    <?php else : ?>
                        <section class="spux-section-stack" aria-labelledby="spux-section-title">
                            <div class="spux-card spux-prose">
                                <h2 id="spux-section-title"><?php esc_html_e('Overview', 'sabri-public-experience'); ?></h2>
                                <?php if (! empty($profile['headline'])) : ?>
                                    <p class="spux-overview-headline"><?php echo esc_html((string) $profile['headline']); ?></p>
                                <?php endif; ?>
                                <?php if (! empty($profile['bio'])) : ?>
                                    <p><?php echo esc_html(wp_trim_words((string) $profile['bio'], 55)); ?></p>
                                    <?php if (isset($sections['about'])) : ?>
                                        <a class="spux-text-link" href="<?php echo esc_url(trailingslashit((string) $profile['canonical_url'] . 'about')); ?>">
                                            <?php esc_html_e('Read full biography', 'sabri-public-experience'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php elseif (empty($profile['headline'])) : ?>
                                    <div class="spux-state spux-state--empty" role="status">
                                        <p><?php esc_html_e('No public information is available yet.', 'sabri-public-experience'); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
