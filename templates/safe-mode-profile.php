<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="main" class="site-main" tabindex="-1">
    <section aria-labelledby="spux-safe-mode-title">
        <h1 id="spux-safe-mode-title"><?php esc_html_e('Profile temporarily unavailable', 'sabri-public-experience'); ?></h1>
        <p><?php esc_html_e('This public profile is in a protected recovery state. No private profile, contact, publication, or provider data is being exposed while recovery checks are active.', 'sabri-public-experience'); ?></p>
        <p><?php esc_html_e('Please try again after the recovery window.', 'sabri-public-experience'); ?></p>
    </section>
</main>
<?php
get_footer();
