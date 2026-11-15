<?php
/**
 * Regression for the exact File25 WordPress 7.0.1/7.1.4 bootstrap failure:
 * PHP registers unconditional functions before executing the top-level guard.
 * A self-referential function_exists() guard then returned prematurely even
 * though WordPress reported the plugin as activated.
 */
declare(strict_types=1);
$plugin = file_get_contents( dirname( __DIR__ ) . '/sabri-public-experience.php' );
if ( ! is_string( $plugin ) || '' === $plugin ) {
	fwrite( STDERR, "FAIL: File25 plugin entry point not readable.\n" );
	exit( 1 );
}
$checks = 0;
foreach ( array(
	'sabri_public_experience_activate',
	'sabri_public_experience_deactivate',
	'sabri_public_experience_bootstrap',
) as $fn ) {
	$escaped = preg_quote( $fn, '/' );
	$pattern = "/if\s*\(\s*!\s*function_exists\s*\(\s*'{$escaped}'\s*\)\s*\)\s*\{\s*function\s+{$escaped}\s*\(/s";
	if ( 1 !== preg_match( $pattern, $plugin ) ) {
		fwrite( STDERR, "FAIL: {$fn} must be declared inside its own runtime guard.\n" );
		exit( 1 );
	}
	$checks++;
}
$bootstrap_guard = strpos( $plugin, "if (function_exists('sabri_public_experience_bootstrap'))" );
$owned_marker = strpos( $plugin, "define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.15.0')");
$conditional = strpos( $plugin, "if (! function_exists('sabri_public_experience_bootstrap'))");
if ( false === $bootstrap_guard || false === $owned_marker || false === $conditional ||
	! ( $bootstrap_guard < $owned_marker && $owned_marker < $conditional ) ) {
	fwrite( STDERR, "FAIL: external-collision guard must precede source ownership and deferred function definition.\n" );
	exit( 1 );
}
$checks++;
echo "File25 guarded lifecycle bootstrap regression: {$checks} assertions passed\n";
