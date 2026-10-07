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
$secondary = is_array($context['timeline_secondary_filters'] ?? null) ? $context['timeline_secondary_filters'] : [];
$available_sorts = is_array($context['timeline_available_sorts'] ?? null) ? $context['timeline_available_sorts'] : ['latest', 'oldest'];
$effective_sort = is_string($context['timeline_effective_sort'] ?? null) ? (string) $context['timeline_effective_sort'] : 'latest';
$topic = is_string($secondary['topic'] ?? null) ? (string) $secondary['topic'] : '';
$year = is_int($secondary['year'] ?? null) ? (int) $secondary['year'] : 0;
$language = is_string($secondary['language'] ?? null) ? (string) $secondary['language'] : '';
$provider_filter = is_string($secondary['provider'] ?? null) ? (string) $secondary['provider'] : '';
$review_state = is_string($secondary['review_state'] ?? null) ? (string) $secondary['review_state'] : '';
$source_state = is_string($secondary['source_state'] ?? null) ? (string) $secondary['source_state'] : '';
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


<form class="spux-timeline-secondary-filters" method="get" action="<?php echo esc_url(remove_query_arg(['topic', 'year', 'language', 'provider', 'sort', 'review_state', 'source_state', 'paged'])); ?>">
    <?php if ($current_type !== '') : ?>
        <input type="hidden" name="type" value="<?php echo esc_attr($current_type); ?>">
    <?php endif; ?>
    <?php if ($search_query !== '') : ?>
        <input type="hidden" name="profile_q" value="<?php echo esc_attr($search_query); ?>">
    <?php endif; ?>
    <fieldset>
        <legend><?php esc_html_e('Timeline filters', 'sabri-public-experience'); ?></legend>
        <div class="spux-filter-grid">
            <label>
                <span><?php esc_html_e('Topic', 'sabri-public-experience'); ?></span>
                <input type="text" name="topic" maxlength="120" value="<?php echo esc_attr($topic); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Year', 'sabri-public-experience'); ?></span>
                <input type="number" name="year" min="1900" max="<?php echo esc_attr((string) ((int) gmdate('Y') + 1)); ?>" value="<?php echo $year > 0 ? esc_attr((string) $year) : ''; ?>">
            </label>
            <label>
                <span><?php esc_html_e('Language', 'sabri-public-experience'); ?></span>
                <input type="text" name="language" maxlength="35" placeholder="<?php esc_attr_e('For example: en-US', 'sabri-public-experience'); ?>" value="<?php echo esc_attr($language); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Provider', 'sabri-public-experience'); ?></span>
                <input type="text" name="provider" maxlength="64" placeholder="<?php esc_attr_e('Provider key', 'sabri-public-experience'); ?>" value="<?php echo esc_attr($provider_filter); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Sort', 'sabri-public-experience'); ?></span>
                <select name="sort">
                    <option value="latest"<?php selected($effective_sort, 'latest'); ?>><?php esc_html_e('Latest', 'sabri-public-experience'); ?></option>
                    <option value="oldest"<?php selected($effective_sort, 'oldest'); ?>><?php esc_html_e('Oldest', 'sabri-public-experience'); ?></option>
                    <?php if (in_array('most-viewed', $available_sorts, true)) : ?>
                        <option value="most-viewed"<?php selected($effective_sort, 'most-viewed'); ?>><?php esc_html_e('Most viewed', 'sabri-public-experience'); ?></option>
                    <?php endif; ?>
                    <?php if (in_array('most-saved', $available_sorts, true)) : ?>
                        <option value="most-saved"<?php selected($effective_sort, 'most-saved'); ?>><?php esc_html_e('Most saved', 'sabri-public-experience'); ?></option>
                    <?php endif; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Review state', 'sabri-public-experience'); ?></span>
                <select name="review_state">
                    <option value=""><?php esc_html_e('Any review state', 'sabri-public-experience'); ?></option>
                    <?php foreach (['published' => __('Published', 'sabri-public-experience'), 'approved' => __('Approved', 'sabri-public-experience'), 'reviewed' => __('Reviewed', 'sabri-public-experience'), 'corrected' => __('Corrected', 'sabri-public-experience'), 'retracted' => __('Retracted', 'sabri-public-experience')] as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"<?php selected($review_state, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Source state', 'sabri-public-experience'); ?></span>
                <select name="source_state">
                    <option value=""><?php esc_html_e('Any accepted source state', 'sabri-public-experience'); ?></option>
                    <?php foreach (['read-only' => __('Read-only', 'sabri-public-experience'), 'staging-accepted' => __('Staging accepted', 'sabri-public-experience'), 'production-accepted' => __('Production accepted', 'sabri-public-experience')] as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"<?php selected($source_state, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="spux-filter-actions">
            <button class="spux-button" type="submit"><?php esc_html_e('Apply filters', 'sabri-public-experience'); ?></button>
            <a class="spux-button spux-button--secondary" href="<?php echo esc_url(remove_query_arg(['topic', 'year', 'language', 'provider', 'sort', 'review_state', 'source_state', 'paged'])); ?>"><?php esc_html_e('Reset filters', 'sabri-public-experience'); ?></a>
        </div>
    </fieldset>
</form>

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

    <?php if ($has_more) : ?>
        <div class="spux-load-more">
            <a class="spux-button" data-spux-load-more rel="next" href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>" aria-describedby="spux-pagination-status">
                <?php esc_html_e('Load More', 'sabri-public-experience'); ?>
            </a>
        </div>
    <?php endif; ?>

    <?php if ($page > 1 || $has_more) : ?>
        <nav class="spux-pagination" aria-label="<?php esc_attr_e('Timeline pagination fallback', 'sabri-public-experience'); ?>">
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
        <p id="spux-pagination-status" class="screen-reader-text" role="status" aria-live="polite">
            <?php echo esc_html(sprintf(__('Page %1$d. %2$d public items shown on this page.', 'sabri-public-experience'), $page, count($items))); ?>
        </p>
    <?php endif; ?>
<?php endif; ?>
