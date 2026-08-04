<?php
/** @var array<string,mixed> $timeline */
$items = (array) ($timeline['items'] ?? []);
$page = max(1, (int) ($timeline['page'] ?? 1));
$has_more = ! empty($timeline['has_more']);
$partial = ! empty($timeline['provider_errors']);
$truncated = ! empty($timeline['truncated']);
$current_type = sanitize_key((string) ($context['timeline_content_type'] ?? ''));
$search_query = (string) ($context['timeline_search_query'] ?? '');
$search_error = sanitize_key((string) ($context['timeline_search_error'] ?? ''));
$search_enabled = (($context['preferences']['profile_local_search'] ?? null) === true);
$filters = [];
foreach ((array) ($context['timeline_filters'] ?? []) as $type => $label) {
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

<?php if ($search_enabled) : ?>
    <form class="spux-timeline-search" method="get" action="<?php echo esc_url(remove_query_arg(['profile_q', 'paged'])); ?>" role="search">
        <?php if ($current_type !== '') : ?>
            <input type="hidden" name="type" value="<?php echo esc_attr($current_type); ?>">
        <?php endif; ?>
        <label for="spux-profile-search"><?php esc_html_e('Search this profile timeline', 'sabri-public-experience'); ?></label>
        <div class="spux-timeline-search__controls">
            <input id="spux-profile-search" type="search" name="profile_q" value="<?php echo esc_attr($search_query); ?>" maxlength="120" autocomplete="off">
            <button class="spux-button" type="submit"><?php esc_html_e('Search', 'sabri-public-experience'); ?></button>
            <?php if ($search_query !== '') : ?>
                <a class="spux-button spux-button--secondary" href="<?php echo esc_url(remove_query_arg(['profile_q', 'paged'])); ?>"><?php esc_html_e('Clear search', 'sabri-public-experience'); ?></a>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php if ($search_error !== '') : ?>
    <div class="spux-notice spux-notice--warning" role="alert">
        <p><?php esc_html_e('The profile search request was invalid or search is disabled. No unsafe query was executed.', 'sabri-public-experience'); ?></p>
    </div>
<?php endif; ?>

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
        <?php if ($search_query !== '') : ?>
            <h3><?php esc_html_e('No matching public contributions', 'sabri-public-experience'); ?></h3>
            <p><?php esc_html_e('Try a different keyword or clear the profile-local search.', 'sabri-public-experience'); ?></p>
        <?php else : ?>
            <h3><?php esc_html_e('No public publications yet', 'sabri-public-experience'); ?></h3>
            <p><?php esc_html_e('Approved contributions will appear here when their native providers publish them.', 'sabri-public-experience'); ?></p>
        <?php endif; ?>
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
            $published_date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d\TH:i:s\Z',
                $published_at,
                new \DateTimeZone('UTC')
            );
            $published_errors = \DateTimeImmutable::getLastErrors();
            $timestamp = $published_date instanceof \DateTimeImmutable
                && (! is_array($published_errors)
                    || ((int) ($published_errors['warning_count'] ?? 0) === 0
                        && (int) ($published_errors['error_count'] ?? 0) === 0))
                && $published_date->format('Y-m-d\TH:i:s\Z') === $published_at
                    ? $published_date->getTimestamp()
                    : null;
            $correction = sanitize_key((string) ($item['correction_state'] ?? 'none'));
            ?>
            <article class="spux-card spux-timeline-card">
                <div class="spux-card__meta">
                    <span class="spux-type"><?php echo esc_html(ucwords(str_replace('-', ' ', (string) ($item['content_type'] ?? 'publication')))); ?></span>
                    <?php if ($timestamp !== null) : ?>
                        <time datetime="<?php echo esc_attr($published_at); ?>">
                            <?php echo esc_html(wp_date(get_option('date_format'), $timestamp, new \DateTimeZone('UTC'))); ?>
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
