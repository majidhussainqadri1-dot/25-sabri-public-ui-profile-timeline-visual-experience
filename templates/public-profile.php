<?php
/**
 * Public profile template.
 *
 * @var array<string,mixed> $profile
 * @var array<string,mixed> $context
 * @var array<string,mixed> $timeline
 * @var array<string,mixed> $provider_section
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$profile = (array) ($GLOBALS['sabri_public_experience_profile'] ?? []);
$context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
$timeline = (array) ($GLOBALS['sabri_public_experience_timeline'] ?? []);
$provider_section = (array) ($GLOBALS['sabri_public_experience_provider_section'] ?? []);
$metrics = (array) ($GLOBALS['sabri_public_experience_metrics'] ?? []);
$completion_assistant = (array) ($GLOBALS['sabri_public_experience_completion_assistant'] ?? []);
$section = sanitize_key((string) ($context['section'] ?? 'overview')) ?: 'overview';
$sections = (array) ($profile['section_labels'] ?? []);
$profile_class = sanitize_key((string) ($profile['class'] ?? 'member'));
$profile_template = sanitize_key((string) ($profile['profile_template'] ?? 'standard')) ?: 'standard';
$preview_mode = sanitize_key((string) ($context['preview_mode'] ?? ''));
$preview_urls = is_array($context['preview_urls'] ?? null) ? $context['preview_urls'] : [];
$breadcrumbs = array_values(array_filter(
    (array) ($context['breadcrumbs'] ?? []),
    'is_array'
));
?>
<main id="sabri-main-content" class="spux-profile<?php echo $preview_mode !== '' ? ' spux-preview spux-preview--' . esc_attr($preview_mode) : ''; ?>" tabindex="-1" data-spux-profile-class="<?php echo esc_attr($profile_class); ?>" data-spux-section="<?php echo esc_attr($section); ?>" data-spux-density="<?php echo esc_attr((string) ($profile['visual_density'] ?? 'comfortable')); ?>" data-spux-template="<?php echo esc_attr($profile_template); ?>" data-spux-preview-mode="<?php echo esc_attr($preview_mode); ?>" data-spux-cover-focal-point="<?php echo esc_attr((string) ($profile['cover_focal_point'] ?? 'center')); ?>">
    <div class="spux-container sabri-ui-container">
        <?php if (is_404() || $profile === []) : ?>
            <?php
            echo \Sabri\PublicExperience\Components::render_state([
                'type' => 'error',
                'title' => __('Profile unavailable', 'sabri-public-experience'),
                'message' => __('This profile is private, unavailable, or no longer published.', 'sabri-public-experience'),
                'action_url' => home_url('/'),
                'action_label' => __('Return Home', 'sabri-public-experience'),
            ]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer escapes all fields.
            ?>
        <?php else : ?>
            <?php if ($breadcrumbs !== []) : ?>
                <nav class="spux-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'sabri-public-experience'); ?>">
                    <ol>
                        <?php foreach ($breadcrumbs as $index => $item) : ?>
                            <?php
                            $label = isset($item['label']) && is_scalar($item['label']) ? (string) $item['label'] : '';
                            $url = \Sabri\PublicExperience\Public_URL::sanitize_same_site($item['url'] ?? '', false);
                            $current = $index === array_key_last($breadcrumbs);
                            if ($label === '' || $url === '') {
                                continue;
                            }
                            ?>
                            <li>
                                <?php if ($current) : ?>
                                    <span aria-current="page"><?php echo esc_html($label); ?></span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>

            <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/profile-hero.php'; ?>

            <?php if ($preview_mode !== '') : ?>
                <section class="spux-card spux-preview-toolbar" aria-labelledby="spux-preview-title">
                    <h2 id="spux-preview-title"><?php esc_html_e('View as Public Preview', 'sabri-public-experience'); ?></h2>
                    <p><?php echo esc_html(sprintf(__('Preview mode: %s. This is a private, noindex projection. Previewing never changes authorization or public data.', 'sabri-public-experience'), $preview_mode)); ?></p>
                    <?php if ($preview_urls !== []) : ?>
                        <nav class="spux-preview-modes" aria-label="<?php esc_attr_e('Public preview modes', 'sabri-public-experience'); ?>">
                            <?php foreach ($preview_urls as $mode => $url) : ?>
                                <?php
                                $mode = sanitize_key((string) $mode);
                                $url = SabriPublicExperiencePublic_URL::sanitize_same_site($url, false);
                                if ($mode === '' || $url === '') { continue; }
                                $active = $mode === $preview_mode;
                                ?>
                                <a class="spux-button spux-button--secondary<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                                    <?php echo esc_html(ucwords(str_replace('-', ' ', $mode))); ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                    <?php $exit_preview = SabriPublicExperiencePublic_URL::sanitize_same_site($profile['canonical_url'] ?? '', false); ?>
                    <?php if ($exit_preview !== '') : ?>
                        <a class="spux-text-link" href="<?php echo esc_url($exit_preview); ?>"><?php esc_html_e('Exit preview', 'sabri-public-experience'); ?></a>
                    <?php endif; ?>
                </section>

                <?php if ($preview_mode === 'contact') : ?>
                    <section class="spux-card spux-preview-card" aria-labelledby="spux-contact-preview-title">
                        <h2 id="spux-contact-preview-title"><?php esc_html_e('Contact visibility preview', 'sabri-public-experience'); ?></h2>
                        <?php if (empty($profile['contacts'])) : ?>
                            <p><?php esc_html_e('No public contact values are currently exposed by the canonical privacy projection.', 'sabri-public-experience'); ?></p>
                        <?php else : ?>
                            <ul>
                                <?php foreach ((array) $profile['contacts'] as $type => $value) : ?>
                                    <?php if (is_scalar($value) && (string) $value !== '') : ?>
                                        <li><strong><?php echo esc_html(ucwords((string) $type)); ?>:</strong> <?php echo esc_html((string) $value); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php elseif ($preview_mode === 'search') : ?>
                    <section class="spux-card spux-preview-card spux-search-preview" aria-labelledby="spux-search-preview-title">
                        <h2 id="spux-search-preview-title"><?php esc_html_e('Search-engine preview', 'sabri-public-experience'); ?></h2>
                        <p class="spux-search-preview__title"><?php echo esc_html((string) ($profile['display_name'] ?? '')); ?></p>
                        <p class="spux-search-preview__url"><?php echo esc_html((string) ($profile['canonical_url'] ?? '')); ?></p>
                        <p><?php echo esc_html(wp_trim_words((string) ($profile['bio'] ?? $profile['headline'] ?? ''), 30)); ?></p>
                    </section>
                <?php elseif ($preview_mode === 'social') : ?>
                    <section class="spux-card spux-preview-card spux-social-preview" aria-labelledby="spux-social-preview-title">
                        <h2 id="spux-social-preview-title"><?php esc_html_e('Social share-card preview', 'sabri-public-experience'); ?></h2>
                        <?php if (! empty($profile['avatar_url'])) : ?>
                            <img src="<?php echo esc_url((string) $profile['avatar_url']); ?>" alt="" width="160" height="160" loading="lazy">
                        <?php endif; ?>
                        <p class="spux-social-preview__title"><?php echo esc_html((string) ($profile['display_name'] ?? '')); ?></p>
                        <p><?php echo esc_html(wp_trim_words((string) ($profile['bio'] ?? $profile['headline'] ?? ''), 24)); ?></p>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($metrics !== []) : ?>
                <section class="spux-public-metrics" aria-labelledby="spux-public-metrics-title">
                    <h2 id="spux-public-metrics-title" class="spux-sr-only"><?php esc_html_e('Public profile metrics', 'sabri-public-experience'); ?></h2>
                    <dl>
                        <?php foreach ($metrics as $metric => $value) : ?>
                            <div class="spux-public-metrics__item">
                                <dt><?php echo esc_html(ucwords(str_replace('_', ' ', (string) $metric))); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n((int) $value)); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
            <?php endif; ?>

            <?php if (($completion_assistant['authorized'] ?? null) === true) : ?>
                <aside class="spux-card spux-completion-assistant" aria-labelledby="spux-completion-title">
                    <h2 id="spux-completion-title"><?php esc_html_e('Public Profile Completion Assistant', 'sabri-public-experience'); ?></h2>
                    <?php if (($completion_assistant['complete'] ?? null) === true) : ?>
                        <p><?php esc_html_e('All currently governed public-profile fields are complete.', 'sabri-public-experience'); ?></p>
                    <?php else : ?>
                        <p><?php esc_html_e('Complete or review these public-profile items:', 'sabri-public-experience'); ?></p>
                        <ul>
                            <?php foreach ((array) ($completion_assistant['missing'] ?? []) as $missing) : ?>
                                <?php if (is_string($missing) && $missing !== '') : ?>
                                    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $missing))); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php $edit_url = \Sabri\PublicExperience\Public_URL::sanitize_same_site($completion_assistant['edit_url'] ?? '', false); ?>
                    <?php if ($edit_url !== '') : ?>
                        <a class="spux-button spux-button--secondary" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit profile with native owner', 'sabri-public-experience'); ?></a>
                    <?php endif; ?>
                    <?php $assistant_previews = is_array($completion_assistant['preview_urls'] ?? null) ? $completion_assistant['preview_urls'] : []; ?>
                    <?php if ($assistant_previews !== []) : ?>
                        <h3><?php esc_html_e('Preview public projection', 'sabri-public-experience'); ?></h3>
                        <nav class="spux-preview-modes" aria-label="<?php esc_attr_e('Profile preview options', 'sabri-public-experience'); ?>">
                            <?php foreach ($assistant_previews as $mode => $url) : ?>
                                <?php
                                $mode = sanitize_key((string) $mode);
                                $url = SabriPublicExperiencePublic_URL::sanitize_same_site($url, false);
                                if ($mode === '' || $url === '') { continue; }
                                ?>
                                <a class="spux-button spux-button--secondary" href="<?php echo esc_url($url); ?>"><?php echo esc_html(ucwords(str_replace('-', ' ', $mode))); ?></a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                </aside>
            <?php endif; ?>

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
                    <?php elseif (! empty($provider_section['is_provider_section']) && ($provider_section['section'] ?? '') === $section) : ?>
                        <?php require SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/partials/provider-section.php'; ?>
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
                            <div class="spux-card sabri-ui-card spux-prose">
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
                                    <?php
                                    echo \Sabri\PublicExperience\Components::render_state([
                                        'type' => 'empty',
                                        'title' => __('No public information yet', 'sabri-public-experience'),
                                        'message' => __('Approved public profile information will appear here when it becomes available.', 'sabri-public-experience'),
                                        'compact' => true,
                                    ]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer escapes all fields.
                                    ?>
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
