<?php
/** @var array<string,mixed> $profile */
$clinic = (array) ($profile['clinic'] ?? []);
$contacts = (array) ($profile['contacts'] ?? []);
$title = ($profile['class'] ?? '') === 'founder'
    ? __('Clinic and Contact', 'sabri-public-experience')
    : __('Clinic', 'sabri-public-experience');
?>
<section class="spux-section-stack" aria-labelledby="spux-section-title">
    <div class="spux-card spux-prose">
        <h2 id="spux-section-title"><?php echo esc_html($title); ?></h2>
        <?php if ($clinic !== []) : ?>
            <?php if (! empty($clinic['name'])) : ?>
                <h3><?php echo esc_html((string) $clinic['name']); ?></h3>
            <?php endif; ?>
            <dl class="spux-definition-grid">
                <?php if (! empty($clinic['address'])) : ?>
                    <div><dt><?php esc_html_e('Address', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $clinic['address']); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($clinic['city']) || ! empty($clinic['country'])) : ?>
                    <div><dt><?php esc_html_e('Location', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html(implode(', ', array_filter([(string) ($clinic['city'] ?? ''), (string) ($clinic['country'] ?? '')]))); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($clinic['hours'])) : ?>
                    <div><dt><?php esc_html_e('Hours', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $clinic['hours']); ?></dd></div>
                <?php endif; ?>
                <?php if (! empty($clinic['timezone'])) : ?>
                    <div><dt><?php esc_html_e('Time Zone', 'sabri-public-experience'); ?></dt><dd><?php echo esc_html((string) $clinic['timezone']); ?></dd></div>
                <?php endif; ?>
            </dl>
        <?php else : ?>
            <p><?php esc_html_e('No approved public clinic details are currently available.', 'sabri-public-experience'); ?></p>
        <?php endif; ?>
    </div>

    <article class="spux-card spux-prose">
        <h3><?php esc_html_e('Approved Contact Methods', 'sabri-public-experience'); ?></h3>
        <?php if ($contacts !== []) : ?>
            <p><?php esc_html_e('Use the professional contact methods approved for public display.', 'sabri-public-experience'); ?></p>
            <div class="spux-actions">
                <?php foreach ($contacts as $type => $value) : ?>
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
    </article>

    <aside class="spux-notice spux-notice--danger" role="note">
        <strong><?php esc_html_e('Emergency safety', 'sabri-public-experience'); ?></strong>
        <p><?php esc_html_e('Do not delay urgent or emergency care while waiting for an online reply. Contact the nearest qualified local emergency service or clinician.', 'sabri-public-experience'); ?></p>
    </aside>
</section>
