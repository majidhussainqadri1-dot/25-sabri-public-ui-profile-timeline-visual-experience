<?php
/** @var array<string,mixed> $timeline */
$items = (array) ($timeline['items'] ?? []);
$page = max(1, (int) ($timeline['page'] ?? 1));
$has_more = ! empty($timeline['has_more']);
$partial = ! empty($timeline['provider_errors']);
$truncated = ! empty($timeline['truncated']);
$current_type = isset($_GET['type']) && is_scalar($_GET['type'])
    ? sanitize_key((string) wp_unslash($_GET['type']))
    : '';
$base_filters = [
    '' => __('All', 'sabri-public-experience'),
    'post' => __('Posts', 'sabri-public-experience'),
];
$requested_filters = (array) apply_filters(
    'sabri_public_experience/timeline_filters',
    $base_filters,
    (array) ($profile ?? [])
);
$filters = [];
foreach (array_slice($requested_filters, 0, 20, true) as $type => $label) {
    if (! is_scalar($type) || ! is_scalar($label)) {
        continue;
    }
    $type = sanitize_key((string) $type);
    $label = sanitize_text_field((string) $label);
    if ($label !== '' && ($type === '' || preg_match('/^[a-z0-9_\-]{1,64}$/', $type) === 1)) {
        $filters[$type] = $label;
    }
}
if (! isset($filters[''])) {
    $filters = ['' => __('All', 'sabri-public-experience')] + $filters;
}
?>
<div class="spux-section-heading">
    <h2 id="spux-section-title"><?php esc_html_e('Timeline', 'sabri-public-experience'); ?></h2>
    <p><?php esc_html_e('Approved public contributions from their native canonical sources.', 'sabri-public-experience'); ?></p>
</div>

<?php if (count($filters) > 1) : ?>
    <nav class="spux-filter-bar" aria-label="<?php esc_attr_e('Timeline filters', 'sabri-public-experience'); ?>">
        <?php foreach ($filters as $type => $label) : ?>
            <?php
            $url = remove_query_arg(['type', 'paged']);
            if ($type !== '') {
                $url = add_query_arg('type', $type, $url);
            }
            $active = $current_type === $type;
            ?>
            <a class="spux-filter-bar__item<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>>
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if ($partial) : ?>
    <div class="spux-notice spux-notice--warning" role="status">
        <p><?php esc_html_e('Some public contributions could not be loaded. Available verified items are shown below.', 'sabri-public-experience'); ?></p>
    </div>
<?php endif; ?>

<?php if ($truncated) : ?>
    <div class="spux-notice spux-notice--warning" role="status">
        <p><?php esc_html_e('This foundation view reached its safe retrieval limit. A production timeline index is required for deeper history.', 'sabri-public-experience'); ?></p>
    </div>
<?php endif; ?>

<?php if ($items === []) : ?>
    <div class="spux-state spux-state--empty" role="status">
        <h3><?php esc_html_e('No public publications yet', 'sabri-public-experience'); ?></h3>
        <p><?php esc_html_e('Approved contributions will appear here when their native providers publish them.', 'sabri-public-experience'); ?></p>
    </div>
<?php else : ?>
    <div class="spux-timeline">
        <?php foreach ($items as $item) : ?>
            <?php
            $url = esc_url((string) ($item['canonical_url'] ?? ''));
            $title = trim((string) ($item['title'] ?? ''));
            if ($url === '' || $title === '') {
                continue;
            }
            $published_at = (string) ($item['published_at'] ?? '');
            $timestamp = strtotime($published_at);
            $correction = sanitize_key((string) ($item['correction_state'] ?? 'none'));
            ?>
            <article class="spux-card spux-timeline-card">
                <div class="spux-card__meta">
                    <span class="spux-type"><?php echo esc_html(ucwords(str_replace('-', ' ', (string) ($item['content_type'] ?? 'publication')))); ?></span>
                    <?php if ($timestamp !== false) : ?>
                        <time datetime="<?php echo esc_attr($published_at); ?>">
                            <?php echo esc_html(wp_date(get_option('date_format'), $timestamp)); ?>
                        </time>
                    <?php endif; ?>
                </div>
                <?php if (in_array($correction, ['corrected', 'retracted'], true)) : ?>
                    <p class="spux-badge spux-badge--<?php echo esc_attr($correction); ?>">
                        <?php echo esc_html($correction === 'retracted' ? __('Retracted', 'sabri-public-experience') : __('Corrected', 'sabri-public-experience')); ?>
                    </p>
                <?php endif; ?>
                <h3><a href="<?php echo $url; ?>"><?php echo esc_html($title); ?></a></h3>
                <?php if (! empty($item['safe_excerpt'])) : ?>
                    <p><?php echo esc_html((string) $item['safe_excerpt']); ?></p>
                <?php endif; ?>
                <a class="spux-read-more" href="<?php echo $url; ?>" aria-label="<?php echo esc_attr(sprintf(__('Read more: %s', 'sabri-public-experience'), $title)); ?>">
                    <?php esc_html_e('Read More', 'sabri-public-experience'); ?>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($page > 1 || $has_more) : ?>
        <nav class="spux-pagination" aria-label="<?php esc_attr_e('Timeline pagination', 'sabri-public-experience'); ?>">
            <?php if ($page > 1) : ?>
                <a class="spux-button spux-button--secondary" rel="prev" href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>">
                    <?php esc_html_e('Previous page', 'sabri-public-experience'); ?>
                </a>
            <?php endif; ?>
            <span class="spux-pagination__current" aria-current="page">
                <?php echo esc_html(sprintf(__('Page %d', 'sabri-public-experience'), $page)); ?>
            </span>
            <?php if ($has_more) : ?>
                <a class="spux-button spux-button--secondary" rel="next" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>">
                    <?php esc_html_e('Next page', 'sabri-public-experience'); ?>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
