<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** WP-CLI adapter for the read-only File 25 Hostinger staging probe. */
final class Staging_CLI
{
    public function __construct(private Staging_Probe $probe)
    {
    }

    public static function register(Staging_Probe $probe): void
    {
        if (! defined('WP_CLI') || ! WP_CLI || ! class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('sabri file25 staging-probe', new self($probe));
    }

    /**
     * Generate a privacy-safe, read-only staging-preflight report.
     *
     * ## OPTIONS
     *
     * --expected-commit=<sha>
     * : Exact 40-character File 25 candidate commit expected on staging.
     *
     * [--compact]
     * : Emit compact JSON instead of pretty-printed JSON.
     *
     * ## EXAMPLES
     *
     *     wp sabri file25 staging-probe --expected-commit=<sha>
     *
     * @param list<string> $args
     * @param array<string,mixed> $assoc_args
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        unset($args);
        $expected = isset($assoc_args['expected-commit']) && is_scalar($assoc_args['expected-commit'])
            ? (string) $assoc_args['expected-commit']
            : '';
        $report = $this->probe->snapshot($expected);
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (! isset($assoc_args['compact'])) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $json = function_exists('wp_json_encode')
            ? wp_json_encode($report, $flags)
            : json_encode($report, $flags);
        if (! is_string($json)) {
            \WP_CLI::error('File 25 staging-probe report could not be encoded.');
            return;
        }

        \WP_CLI::line($json);
        if (empty($report['ready_for_manual_staging_tests'])) {
            \WP_CLI::halt(2);
        }
    }
}
