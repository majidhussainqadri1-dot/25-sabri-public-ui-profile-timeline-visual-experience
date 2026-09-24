<?php
/** @var array<string,mixed> $provider_section */

if (! defined('ABSPATH')) {
    exit;
}

$label = (string) ($provider_section['label'] ?? __('Public Content', 'sabri-public-experience'));
$items = (array) ($provider_section['items'] ?? []);
$error_count = max(0, (int) ($provider_section['provider_error_count'] ?? 0));
$truncated = ! empty($provider_section['truncated']);
?>
<section class="spux-section-stack" aria-labelledby="spux-section-title">
    <div class="spux-section-heading">
        <h2 id="spux-section-title"><?php echo esc_html($label); ?></h2>
        <p><?php esc_html_e('Approved public material supplied by its native platform owner.', 'sabri-public-experience'); ?></p>
    </div>

    <?php if ($error_count > 0) : ?>
        <?php
        echo \Sabri\PublicExperience\Components::render_notice([
            'type' => 'warning',
            'title' => __('Some sources are temporarily unavailable', 'sabri-public-experience'),
            'message' => __('Available verified material is shown. No private or unverified fallback data was used.', 'sabri-public-experience'),
        ]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- canonical component escapes all fields.
        ?>
    <?php endif; ?>

    <?php if ($truncated) : ?>
        <?php
        echo \Sabri\PublicExperience\Components::render_notice([
            'type' => 'info',
            'title' => __('Safe display limit reached', 'sabri-public-experience'),
            'message' => __('This section is showing a bounded public subset. Deeper history remains with the native module.', 'sabri-public-experience'),
        ]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- canonical component escapes all fields.
        ?>
    <?php endif; ?>

    <?php if ($items === []) : ?>
        <?php
        echo \Sabri\PublicExperience\Components::render_state([
            'type' => 'empty',
            'title' => __('No approved public material yet', 'sabri-public-experience'),
            'message' => __('Content will appear after an accepted native provider publishes an approved public item.', 'sabri-public-experience'),
        ]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- canonical component escapes all fields.
        ?>
    <?php else : ?>
        <div class="sabri-ui-grid spux-provider-section-grid">
            <?php foreach ($items as $item_html) : ?>
                <?php if (is_string($item_html) && $item_html !== '') : ?>
                    <?php echo $item_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated only by File 25 Content_Cards. ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
