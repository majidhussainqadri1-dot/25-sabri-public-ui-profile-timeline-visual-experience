<?php
/** @var array<string,mixed> $timeline */
$items = (array) ($timeline['items'] ?? []);
$page = max(1, (int) ($timeline['page'] ?? 1));
$has_more = ! empty($timeline['has_more']);
$partial = ! empty($timeline['provider_errors']);
$truncated = ! empty($timeline['truncated']);
$current_type = sanitize_key((string) ($context['timeline_content_type'] ?? ''));
$current_provider = sanitize_key((string) ($context['timeline_provider'] ?? ''));
$current_year = (string) ($context['timeline_year'] ?? '');
$current_language = (string) ($context['timeline_language'] ?? '');
$current_topic = (string) ($context['timeline_topic'] ?? '');
$current_sort = in_array((string) ($context['timeline_sort'] ?? 'latest'), ['latest', 'oldest'], true)
    ? (string) ($context['timeline_sort'] ?? 'latest')
    : 'latest';
$preview_mode = sanitize_key((string) ($context['preview_mode'] ?? ''));
$search_query = (string) ($context['timeline_search_query'] ?? '');
$search_error = sanitize_key((string) ($context['timeline_search_error'] ?? ''));
$search_enabled = (($context['preferences']['profile_local_search'] ?? null) === true);
$provider_filters = [];
foreach ((array) ($context['timeline_provider_filters'] ?? []) as $provider_id => $provider_label) {
    if (! is_scalar($provider_id) || ! is_scalar($provider_label)) {
        continue;
    }
    $provider_id = sanitize_key((string) $provider_id);
    $provider_label = sanitize_text_field((string) $provider_label);
    if ($provider_id !== '' && $provider_label !== '') {
        $provider_filters[$provider_id] = $provider_label;
    }
}
$secondary_active = $current_provider !== ''
    || $current_year !== ''
    || $current_language !== ''
    || $current_topic !== ''
    || $current_sort !== 'latest';
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
        <?php if ($current_type !== '') : ?><input type="hidden" name="type" value="<?php echo esc_attr($current_type); ?>"><?php endif; ?>
        <?php if ($current_provider !== '') : ?><input type="hidden" name="provider" value="<?php echo esc_attr($current_provider); ?>"><?php endif; ?>
        <?php if ($current_year !== '') : ?><input type="hidden" name="year" value="<?php echo esc_attr($current_year); ?>"><?php endif; ?>
        <?php if ($current_language !== '') : ?><input type="hidden" name="language" value="<?php echo esc_attr($current_language); ?>"><?php endif; ?>
        <?php if ($current_topic !== '') : ?><input type="hidden" name="topic" value="<?php echo esc_attr($current_topic); ?>"><?php endif; ?>
        <?php if ($current_sort !== 'latest') : ?><input type="hidden" name="sort" value="<?php echo esc_attr($current_sort); ?>"><?php endif; ?>
        <?php if ($preview_mode !== '') : ?><input type="hidden" name="spux_preview" value="<?php echo esc_attr($preview_mode); ?>"><?php endif; ?>
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

<form class="spux-timeline-secondary-filters" method="get" action="<?php echo esc_url(remove_query_arg(['provider', 'year', 'language', 'topic', 'sort', 'paged'])); ?>">
    <?php if ($current_type !== '') : ?><input type="hidden" name="type" value="<?php echo esc_attr($current_type); ?>"><?php endif; ?>
    <?php if ($search_query !== '') : ?><input type="hidden" name="profile_q" value="<?php echo esc_attr($search_query); ?>"><?php endif; ?>
    <?php if ($preview_mode !== '') : ?><input type="hidden" name="spux_preview" value="<?php echo esc_attr($preview_mode); ?>"><?php endif; ?>
    <fieldset>
        <legend><?php esc_html_e('Refine timeline', 'sabri-public-experience'); ?></legend>
        <div class="spux-timeline-secondary-filters__grid">
            <?php if ($provider_filters !== []) : ?>
                <label>
                    <span><?php esc_html_e('Provider', 'sabri-public-experience'); ?></span>
                    <select name="provider">
                        <option value=""><?php esc_html_e('All providers', 'sabri-public-experience'); ?></option>
                        <?php foreach ($provider_filters as $provider_id => $provider_label) : ?>
                            <option value="<?php echo esc_attr($provider_id); ?>"<?php selected($current_provider, $provider_id); ?>><?php echo esc_html($provider_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <label>
                <span><?php esc_html_e('Year', 'sabri-public-experience'); ?></span>
                <input type="text" name="year" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" value="<?php echo esc_attr($current_year); ?>" placeholder="<?php echo esc_attr((string) gmdate('Y')); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Language', 'sabri-public-experience'); ?></span>
                <input type="text" name="language" maxlength="35" value="<?php echo esc_attr($current_language); ?>" placeholder="en-US">
            </label>
            <label>
                <span><?php esc_html_e('Topic', 'sabri-public-experience'); ?></span>
                <input type="text" name="topic" maxlength="120" value="<?php echo esc_attr($current_topic); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Sort', 'sabri-public-experience'); ?></span>
                <select name="sort">
                    <option value="latest"<?php selected($current_sort, 'latest'); ?>><?php esc_html_e('Latest', 'sabri-public-experience'); ?></option>
                    <option value="oldest"<?php selected($current_sort, 'oldest'); ?>><?php esc_html_e('Oldest', 'sabri-public-experience'); ?></option>
                </select>
            </label>
        </div>
        <div class="spux-timeline-secondary-filters__actions">
            <button class="spux-button spux-button--secondary" type="submit"><?php esc_html_e('Apply filters', 'sabri-public-experience'); ?></button>
            <?php if ($secondary_active) : ?>
                <a class="spux-button spux-button--secondary" href="<?php echo esc_url(remove_query_arg(['provider', 'year', 'language', 'topic', 'sort', 'paged'])); ?>"><?php esc_html_e('Clear filters', 'sabri-public-experience'); ?></a>
            <?php endif; ?>
        </div>
    </fieldset>
</form>

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
        <?php if ($search_query !== '' || $current_type !== '' || $secondary_active) : ?>
            <h3><?php esc_html_e('No matching public contributions', 'sabri-public-experience'); ?></h3>
            <p><?php esc_html_e('Try different filters or clear the current timeline refinement.', 'sabri-public-experience'); ?></p>
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

    <p class="spux-sr-only" role="status" aria-live="polite">
        <?php echo esc_html(sprintf(__('Timeline page %1$d loaded with %2$d public items.', 'sabri-public-experience'), $page, count($items))); ?>
    </p>
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
