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
?>
<main id="sabri-main-content" class="spux-profile" tabindex="-1">
    <a class="spux-skip-target" id="profile-content"></a>
    <div class="spux-container">
        <?php if (is_404() || $profile === []) : ?>
            <section class="spux-state spux-state--error" role="status">
                <h1><?php esc_html_e('Profile unavailable', 'sabri-public-experience'); ?></h1>
                <p><?php esc_html_e('This profile is private, unavailable, or no longer published.', 'sabri-public-experience'); ?></p>
                <a class="spux-button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Return Home', 'sabri-public-experience'); ?></a>
            </section>
        <?php else : ?>
            <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/profile-hero.php'; ?>

            <nav class="spux-tabs" aria-label="<?php esc_attr_e('Profile sections', 'sabri-public-experience'); ?>">
                <?php
                $sections = match ((string) ($profile['class'] ?? 'member')) {
                    'founder' => [
                        'overview' => __('Overview', 'sabri-public-experience'),
                        'timeline' => __('Timeline', 'sabri-public-experience'),
                        'knowledge' => __('Knowledge', 'sabri-public-experience'),
                        'books-research' => __('Books and Research', 'sabri-public-experience'),
                        'media' => __('Media', 'sabri-public-experience'),
                        'clinic-contact' => __('Clinic and Contact', 'sabri-public-experience'),
                        'about' => __('About', 'sabri-public-experience'),
                    ],
                    'doctor' => [
                        'overview' => __('Overview', 'sabri-public-experience'),
                        'timeline' => __('Timeline', 'sabri-public-experience'),
                        'knowledge' => __('Knowledge', 'sabri-public-experience'),
                        'media' => __('Media', 'sabri-public-experience'),
                        'clinic' => __('Clinic', 'sabri-public-experience'),
                        'reviews' => __('Reviews', 'sabri-public-experience'),
                        'about' => __('About', 'sabri-public-experience'),
                    ],
                    default => [
                        'overview' => __('Overview', 'sabri-public-experience'),
                        'timeline' => __('Public Contributions', 'sabri-public-experience'),
                        'about' => __('About', 'sabri-public-experience'),
                    ],
                };
                foreach ($sections as $slug => $label) :
                    $active = ($context['section'] ?? 'overview') === $slug;
                    $base = ($profile['class'] ?? '') === 'founder'
                        ? home_url('/founder/')
                        : (($profile['class'] ?? '') === 'doctor'
                            ? home_url('/doctors/' . rawurlencode((string) $profile['slug']) . '/')
                            : home_url('/profile/' . rawurlencode((string) $profile['slug']) . '/'));
                    $url = $slug === 'overview' ? $base : trailingslashit($base . $slug);
                    ?>
                    <a class="spux-tabs__item<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="spux-layout">
                <section class="spux-main" aria-labelledby="spux-section-title">
                    <?php if (($context['section'] ?? 'overview') === 'timeline') : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/timeline.php'; ?>
                    <?php else : ?>
                        <div class="spux-card spux-prose">
                            <h2 id="spux-section-title"><?php echo esc_html(ucwords(str_replace('-', ' ', (string) ($context['section'] ?? 'overview')))); ?></h2>
                            <?php if (($context['section'] ?? 'overview') === 'overview' && ! empty($profile['bio'])) : ?>
                                <div><?php echo wp_kses_post(wpautop((string) $profile['bio'])); ?></div>
                            <?php else : ?>
                                <div class="spux-state spux-state--empty" role="status">
                                    <p><?php esc_html_e('No public information is available in this section yet.', 'sabri-public-experience'); ?></p>
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
