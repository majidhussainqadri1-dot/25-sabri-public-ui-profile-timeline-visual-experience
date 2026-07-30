<?php
/** @var array<string,mixed> $timeline */
$items = (array) ($timeline['items'] ?? []);
?>
<div class="spux-section-heading">
    <h2 id="spux-section-title"><?php esc_html_e('Timeline', 'sabri-public-experience'); ?></h2>
    <p><?php esc_html_e('Approved public contributions from their native canonical sources.', 'sabri-public-experience'); ?></p>
</div>

<?php if ($items === []) : ?>
    <div class="spux-state spux-state--empty" role="status">
        <h3><?php esc_html_e('No public publications yet', 'sabri-public-experience'); ?></h3>
        <p><?php esc_html_e('Approved contributions will appear here when their native providers publish them.', 'sabri-public-experience'); ?></p>
    </div>
<?php else : ?>
    <div class="spux-timeline" aria-live="polite">
        <?php foreach ($items as $item) : ?>
            <article class="spux-card spux-timeline-card">
                <div class="spux-card__meta">
                    <span class="spux-type"><?php echo esc_html(ucwords(str_replace('-', ' ', (string) ($item['content_type'] ?? 'publication')))); ?></span>
                    <time datetime="<?php echo esc_attr((string) ($item['published_at'] ?? '')); ?>">
                        <?php echo esc_html(wp_date(get_option('date_format'), strtotime((string) ($item['published_at'] ?? 'now')))); ?>
                    </time>
                </div>
                <h3><a href="<?php echo esc_url((string) ($item['canonical_url'] ?? '#')); ?>"><?php echo esc_html((string) ($item['title'] ?? '')); ?></a></h3>
                <?php if (! empty($item['safe_excerpt'])) : ?>
                    <p><?php echo esc_html((string) $item['safe_excerpt']); ?></p>
                <?php endif; ?>
                <a class="spux-read-more" href="<?php echo esc_url((string) ($item['canonical_url'] ?? '#')); ?>">
                    <?php esc_html_e('Read More', 'sabri-public-experience'); ?>
                </a>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (! empty($timeline['has_more'])) : ?>
        <nav class="spux-pagination" aria-label="<?php esc_attr_e('Timeline pagination', 'sabri-public-experience'); ?>">
            <a class="spux-button spux-button--secondary" href="<?php echo esc_url(add_query_arg('paged', ((int) ($timeline['page'] ?? 1)) + 1)); ?>">
                <?php esc_html_e('Load More', 'sabri-public-experience'); ?>
            </a>
        </nav>
    <?php endif; ?>
<?php endif; ?>
