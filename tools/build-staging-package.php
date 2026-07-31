<?php

declare(strict_types=1);

/**
 * Deterministic File 25 staging-package builder.
 *
 * Usage:
 * php tools/build-staging-package.php \
 *   --output-dir=build/staging-package \
 *   --commit=<40-character-sha> \
 *   --source-date-epoch=<unix-timestamp>
 */
final class File25_Staging_Package_Builder
{
    private const PACKAGE_ROOT = 'sabri-public-experience';
    private const MANIFEST_NAME = 'STAGING-MANIFEST.json';
    private const TOP_LEVEL_FILES = [
        'sabri-public-experience.php',
        'uninstall.php',
        'readme.txt',
        'README.md',
        'CHANGELOG.md',
        'SECURITY.md',
        'PRIVACY.md',
    ];
    private const RUNTIME_DIRECTORIES = [
        'assets',
        'config',
        'includes',
        'languages',
        'templates',
    ];
    private const FORBIDDEN_SEGMENTS = [
        '.git',
        '.github',
        'build',
        'coverage',
        'docs',
        'node_modules',
        'tests',
        'tools',
        'vendor',
    ];

    /** @return list<string> */
    public static function discover_payload(string $root): array
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $payload = [];

        foreach (self::TOP_LEVEL_FILES as $relative) {
            if (is_file($root . '/' . $relative)) {
                $payload[] = $relative;
            }
        }

        foreach (self::RUNTIME_DIRECTORIES as $directory) {
            $absolute = $root . '/' . $directory;
            if (! is_dir($absolute)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->isLink()) {
                    continue;
                }
                $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
                if (self::payload_path_is_allowed($relative)) {
                    $payload[] = $relative;
                }
            }
        }

        $payload = array_values(array_unique($payload));
        sort($payload, SORT_STRING);

        foreach (['sabri-public-experience.php', 'uninstall.php', 'readme.txt'] as $required) {
            if (! in_array($required, $payload, true)) {
                throw new RuntimeException('Required staging payload file is missing: ' . $required);
            }
        }

        return $payload;
    }

    /** @return array<string,mixed> */
    public static function build(string $root, string $output_dir, string $commit_sha, int $source_date_epoch): array
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP Zip extension is required to build the staging package.');
        }
        if (preg_match('/^[a-f0-9]{40}$/', strtolower($commit_sha)) !== 1) {
            throw new InvalidArgumentException('A full 40-character commit SHA is required.');
        }
        if ($source_date_epoch < 315532800 || $source_date_epoch > 4102444800) {
            throw new InvalidArgumentException('SOURCE_DATE_EPOCH is outside the supported deterministic range.');
        }

        $root = rtrim(str_replace('\\', '/', $root), '/');
        $output_dir = self::validated_output_dir($root, $output_dir);
        self::remove_tree($output_dir);
        if (! mkdir($output_dir, 0775, true) && ! is_dir($output_dir)) {
            throw new RuntimeException('Unable to create staging output directory.');
        }

        $version = self::read_version($root);
        $payload = self::discover_payload($root);
        $stage_root = $output_dir . '/stage/' . self::PACKAGE_ROOT;
        if (! mkdir($stage_root, 0775, true) && ! is_dir($stage_root)) {
            throw new RuntimeException('Unable to create staging payload root.');
        }

        $manifest_files = [];
        foreach ($payload as $relative) {
            $source = $root . '/' . $relative;
            $destination = $stage_root . '/' . $relative;
            $destination_dir = dirname($destination);
            if (! is_dir($destination_dir) && ! mkdir($destination_dir, 0775, true) && ! is_dir($destination_dir)) {
                throw new RuntimeException('Unable to create payload directory: ' . $relative);
            }
            if (! copy($source, $destination)) {
                throw new RuntimeException('Unable to copy payload file: ' . $relative);
            }
            chmod($destination, 0644);
            touch($destination, $source_date_epoch);
            $manifest_files[$relative] = [
                'sha256' => hash_file('sha256', $destination),
                'bytes' => filesize($destination),
            ];
        }

        $manifest = [
            'schema_version' => 1,
            'package' => self::PACKAGE_ROOT,
            'file_number' => 25,
            'canonical_name' => 'Sabri Unified Global Visual Experience and Design System',
            'version' => $version,
            'commit_sha' => strtolower($commit_sha),
            'source_date_epoch' => $source_date_epoch,
            'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z', $source_date_epoch),
            'manifest_scope' => 'Payload files only; the manifest and detached package checksum are excluded from the payload hash list.',
            'files' => $manifest_files,
        ];
        $manifest_json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $internal_manifest = $stage_root . '/' . self::MANIFEST_NAME;
        file_put_contents($internal_manifest, $manifest_json, LOCK_EX);
        chmod($internal_manifest, 0644);
        touch($internal_manifest, $source_date_epoch);

        $base_name = self::PACKAGE_ROOT . '-' . $version;
        $zip_path = $output_dir . '/' . $base_name . '.zip';
        $manifest_path = $output_dir . '/' . $base_name . '-manifest.json';
        $checksum_path = $output_dir . '/' . $base_name . '.sha256';
        file_put_contents($manifest_path, $manifest_json, LOCK_EX);
        touch($manifest_path, $source_date_epoch);

        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create staging ZIP archive.');
        }

        $archive_files = array_merge($payload, [self::MANIFEST_NAME]);
        sort($archive_files, SORT_STRING);
        foreach ($archive_files as $relative) {
            $source = $stage_root . '/' . $relative;
            $archive_name = self::PACKAGE_ROOT . '/' . $relative;
            if (! $zip->addFile($source, $archive_name)) {
                $zip->close();
                throw new RuntimeException('Unable to add package entry: ' . $relative);
            }
            if (method_exists($zip, 'setMtimeName')) {
                $zip->setMtimeName($archive_name, $source_date_epoch);
            }
            if (method_exists($zip, 'setExternalAttributesName')) {
                $zip->setExternalAttributesName($archive_name, ZipArchive::OPSYS_UNIX, 0100644 << 16);
            }
        }
        $zip->setArchiveComment('File 25 staging candidate ' . $version . ' @ ' . strtolower($commit_sha));
        if (! $zip->close()) {
            throw new RuntimeException('Unable to finalize staging ZIP archive.');
        }
        touch($zip_path, $source_date_epoch);

        $zip_sha256 = hash_file('sha256', $zip_path);
        $checksum_line = $zip_sha256 . '  ' . basename($zip_path) . "\n";
        file_put_contents($checksum_path, $checksum_line, LOCK_EX);
        touch($checksum_path, $source_date_epoch);

        self::verify_archive($zip_path, $manifest, $manifest_json);

        return [
            'version' => $version,
            'commit_sha' => strtolower($commit_sha),
            'source_date_epoch' => $source_date_epoch,
            'zip' => $zip_path,
            'zip_sha256' => $zip_sha256,
            'checksum' => $checksum_path,
            'manifest' => $manifest_path,
            'payload_file_count' => count($payload),
        ];
    }

    /** @param array<string,mixed> $manifest */
    private static function verify_archive(string $zip_path, array $manifest, string $manifest_json): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Unable to reopen staging ZIP for verification.');
        }

        $expected = [];
        foreach (array_keys((array) ($manifest['files'] ?? [])) as $relative) {
            $expected[] = self::PACKAGE_ROOT . '/' . $relative;
        }
        $expected[] = self::PACKAGE_ROOT . '/' . self::MANIFEST_NAME;
        sort($expected, SORT_STRING);

        $actual = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name) || ! self::archive_name_is_safe($name)) {
                $zip->close();
                throw new RuntimeException('Unsafe entry detected in staging ZIP.');
            }
            $actual[] = $name;
        }
        sort($actual, SORT_STRING);
        if ($actual !== $expected) {
            $zip->close();
            throw new RuntimeException('Staging ZIP entries do not match the manifest payload.');
        }

        foreach ((array) ($manifest['files'] ?? []) as $relative => $metadata) {
            $name = self::PACKAGE_ROOT . '/' . $relative;
            $contents = $zip->getFromName($name);
            if (! is_string($contents)) {
                $zip->close();
                throw new RuntimeException('Unable to read staged payload entry: ' . $relative);
            }
            if (! hash_equals((string) ($metadata['sha256'] ?? ''), hash('sha256', $contents))) {
                $zip->close();
                throw new RuntimeException('Payload checksum mismatch: ' . $relative);
            }
            if ((int) ($metadata['bytes'] ?? -1) !== strlen($contents)) {
                $zip->close();
                throw new RuntimeException('Payload byte-size mismatch: ' . $relative);
            }
        }

        $embedded_manifest = $zip->getFromName(self::PACKAGE_ROOT . '/' . self::MANIFEST_NAME);
        $zip->close();
        if (! is_string($embedded_manifest) || ! hash_equals($manifest_json, $embedded_manifest)) {
            throw new RuntimeException('Embedded staging manifest does not match the detached manifest.');
        }
    }

    private static function read_version(string $root): string
    {
        $main = file_get_contents($root . '/sabri-public-experience.php');
        $readme = file_get_contents($root . '/readme.txt');
        if (! is_string($main) || ! is_string($readme)) {
            throw new RuntimeException('Unable to read plugin version sources.');
        }
        preg_match('/^\s*\* Version:\s*([^\s]+)/m', $main, $header_match);
        preg_match('/^Stable tag:\s*([^\s]+)/mi', $readme, $stable_match);
        preg_match("/define\('SABRI_PUBLIC_EXPERIENCE_VERSION',\s*'([^']+)'\)/", $main, $constant_match);
        $versions = [$header_match[1] ?? '', $stable_match[1] ?? '', $constant_match[1] ?? ''];
        if (in_array('', $versions, true) || count(array_unique($versions)) !== 1) {
            throw new RuntimeException('Plugin header, constant, and readme versions must match before packaging.');
        }
        if (preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $versions[0]) !== 1) {
            throw new RuntimeException('Plugin version is not a valid semantic version.');
        }

        return $versions[0];
    }

    private static function payload_path_is_allowed(string $relative): bool
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '\\') || str_contains($relative, "\0")) {
            return false;
        }
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || str_starts_with($segment, '.')) {
                return false;
            }
            if (in_array($segment, self::FORBIDDEN_SEGMENTS, true)) {
                return false;
            }
        }

        return true;
    }

    private static function archive_name_is_safe(string $name): bool
    {
        if (! str_starts_with($name, self::PACKAGE_ROOT . '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
            return false;
        }
        foreach (explode('/', $name) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private static function validated_output_dir(string $root, string $output_dir): string
    {
        $output_dir = trim(str_replace('\\', '/', $output_dir));
        if (preg_match('#^build/[A-Za-z0-9._/-]+$#', $output_dir) !== 1 || str_contains($output_dir, '..')) {
            throw new InvalidArgumentException('Output directory must be a safe relative path below build/.');
        }

        return $root . '/' . rtrim($output_dir, '/');
    }

    private static function remove_tree(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            unlink($path);
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir() && ! $item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}

if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        $options = getopt('', ['output-dir:', 'commit:', 'source-date-epoch:']);
        $output_dir = is_array($options) && is_string($options['output-dir'] ?? null) ? $options['output-dir'] : '';
        $commit = is_array($options) && is_string($options['commit'] ?? null) ? strtolower($options['commit']) : '';
        $epoch_raw = is_array($options) && is_scalar($options['source-date-epoch'] ?? null) ? (string) $options['source-date-epoch'] : '';
        if ($output_dir === '' || $commit === '' || preg_match('/^\d{9,10}$/', $epoch_raw) !== 1) {
            throw new InvalidArgumentException('Required options: --output-dir, --commit, --source-date-epoch.');
        }

        $result = File25_Staging_Package_Builder::build(dirname(__DIR__), $output_dir, $commit, (int) $epoch_raw);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . "\n");
        exit(1);
    }
}
