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
    private const MAX_PAYLOAD_FILES = 256;
    private const MAX_PAYLOAD_BYTES = 25_000_000;
    private const MAX_SINGLE_FILE_BYTES = 5_000_000;

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
        $root = self::validated_root($root);
        $payload = [];

        foreach (self::TOP_LEVEL_FILES as $relative) {
            $absolute = $root . '/' . $relative;
            if (is_file($absolute) && ! is_link($absolute)) {
                $payload[] = $relative;
            }
        }

        foreach (self::RUNTIME_DIRECTORIES as $directory) {
            $absolute = $root . '/' . $directory;
            if (! is_dir($absolute) || is_link($absolute)) {
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
                $real = $file->getRealPath();
                if (! is_string($real) || ! self::path_is_within($real, $root)) {
                    throw new RuntimeException('Payload file resolved outside the repository root.');
                }
                $relative = ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
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
        if (count($payload) > self::MAX_PAYLOAD_FILES) {
            throw new RuntimeException('Staging payload contains too many files.');
        }

        return $payload;
    }

    /** @return array<string,mixed> */
    public static function build(string $root, string $output_dir, string $commit_sha, int $source_date_epoch): array
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP Zip extension is required to build the staging package.');
        }
        $commit_sha = strtolower(trim($commit_sha));
        if (preg_match('/^[a-f0-9]{40}$/', $commit_sha) !== 1) {
            throw new InvalidArgumentException('A full 40-character commit SHA is required.');
        }
        if ($source_date_epoch < 315532800 || $source_date_epoch > 4102444800) {
            throw new InvalidArgumentException('SOURCE_DATE_EPOCH is outside the supported deterministic range.');
        }

        $root = self::validated_root($root);
        $output_dir = self::validated_output_dir($root, $output_dir);
        self::remove_tree($output_dir, $root . '/build');
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
        $payload_bytes = 0;
        foreach ($payload as $relative) {
            $source = $root . '/' . $relative;
            if (! is_file($source) || is_link($source)) {
                throw new RuntimeException('Payload source is missing or is a symbolic link: ' . $relative);
            }
            $source_real = realpath($source);
            if (! is_string($source_real) || ! self::path_is_within($source_real, $root)) {
                throw new RuntimeException('Payload source resolved outside the repository root: ' . $relative);
            }
            $bytes = filesize($source_real);
            if (! is_int($bytes) || $bytes < 0 || $bytes > self::MAX_SINGLE_FILE_BYTES) {
                throw new RuntimeException('Payload file exceeds the permitted size: ' . $relative);
            }
            $payload_bytes += $bytes;
            if ($payload_bytes > self::MAX_PAYLOAD_BYTES) {
                throw new RuntimeException('Total staging payload exceeds the permitted size.');
            }

            $destination = $stage_root . '/' . $relative;
            $destination_dir = dirname($destination);
            if (! is_dir($destination_dir) && ! mkdir($destination_dir, 0775, true) && ! is_dir($destination_dir)) {
                throw new RuntimeException('Unable to create payload directory: ' . $relative);
            }
            if (! copy($source_real, $destination)) {
                throw new RuntimeException('Unable to copy payload file: ' . $relative);
            }
            chmod($destination, 0644);
            touch($destination, $source_date_epoch);
            $manifest_files[$relative] = [
                'sha256' => hash_file('sha256', $destination),
                'bytes' => $bytes,
            ];
        }

        $manifest = [
            'schema_version' => 1,
            'package' => self::PACKAGE_ROOT,
            'file_number' => 25,
            'canonical_name' => 'Sabri Unified Global Visual Experience and Design System',
            'version' => $version,
            'commit_sha' => $commit_sha,
            'source_date_epoch' => $source_date_epoch,
            'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z', $source_date_epoch),
            'manifest_scope' => 'Payload files only; the manifest and detached package checksum are excluded from the payload hash list.',
            'files' => $manifest_files,
        ];
        $manifest_json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $internal_manifest = $stage_root . '/' . self::MANIFEST_NAME;
        self::write_file($internal_manifest, $manifest_json, $source_date_epoch);

        $base_name = self::PACKAGE_ROOT . '-' . $version;
        $zip_path = $output_dir . '/' . $base_name . '.zip';
        $manifest_path = $output_dir . '/' . $base_name . '-manifest.json';
        $checksum_path = $output_dir . '/' . $base_name . '.sha256';
        self::write_file($manifest_path, $manifest_json, $source_date_epoch);

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
        $zip->setArchiveComment('File 25 staging candidate ' . $version . ' @ ' . $commit_sha);
        if (! $zip->close()) {
            throw new RuntimeException('Unable to finalize staging ZIP archive.');
        }
        touch($zip_path, $source_date_epoch);

        $zip_sha256 = hash_file('sha256', $zip_path);
        if (! is_string($zip_sha256) || preg_match('/^[a-f0-9]{64}$/', $zip_sha256) !== 1) {
            throw new RuntimeException('Unable to calculate the staging ZIP checksum.');
        }
        self::write_file($checksum_path, $zip_sha256 . '  ' . basename($zip_path) . "\n", $source_date_epoch);

        self::verify_archive($zip_path, $manifest, $manifest_json);

        return [
            'version' => $version,
            'commit_sha' => $commit_sha,
            'source_date_epoch' => $source_date_epoch,
            'zip' => $zip_path,
            'zip_sha256' => $zip_sha256,
            'checksum' => $checksum_path,
            'manifest' => $manifest_path,
            'payload_file_count' => count($payload),
            'payload_bytes' => $payload_bytes,
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
        $seen = [];
        $total_bytes = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            if (! is_string($name) || ! is_array($stat) || ! self::archive_name_is_safe($name)) {
                $zip->close();
                throw new RuntimeException('Unsafe entry detected in staging ZIP.');
            }
            if (isset($seen[$name])) {
                $zip->close();
                throw new RuntimeException('Duplicate entry detected in staging ZIP: ' . $name);
            }
            $seen[$name] = true;
            $entry_bytes = (int) ($stat['size'] ?? -1);
            if ($entry_bytes < 0 || $entry_bytes > self::MAX_SINGLE_FILE_BYTES) {
                $zip->close();
                throw new RuntimeException('Staging ZIP entry exceeds the permitted size: ' . $name);
            }
            $total_bytes += $entry_bytes;
            if ($total_bytes > self::MAX_PAYLOAD_BYTES + self::MAX_SINGLE_FILE_BYTES) {
                $zip->close();
                throw new RuntimeException('Staging ZIP expands beyond the permitted size.');
            }
            if (self::zip_entry_is_symlink($stat)) {
                $zip->close();
                throw new RuntimeException('Symbolic link detected in staging ZIP: ' . $name);
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

    private static function validated_root(string $root): string
    {
        if ($root === '' || str_contains($root, "\0") || is_link($root)) {
            throw new InvalidArgumentException('Repository root is invalid or symbolic.');
        }
        $real = realpath($root);
        if (! is_string($real) || ! is_dir($real)) {
            throw new InvalidArgumentException('Repository root does not exist.');
        }

        return rtrim(str_replace('\\', '/', $real), '/');
    }

    private static function validated_output_dir(string $root, string $output_dir): string
    {
        $output_dir = trim(str_replace('\\', '/', $output_dir));
        if (preg_match('#^build/[A-Za-z0-9][A-Za-z0-9._-]*(?:/[A-Za-z0-9][A-Za-z0-9._-]*)*$#', $output_dir) !== 1) {
            throw new InvalidArgumentException('Output directory must be a safe non-root path below build/.');
        }

        $build_root = $root . '/build';
        if (is_link($build_root)) {
            throw new RuntimeException('The build directory may not be a symbolic link.');
        }
        if (! is_dir($build_root) && ! mkdir($build_root, 0775, true) && ! is_dir($build_root)) {
            throw new RuntimeException('Unable to create the build directory.');
        }
        $build_real = realpath($build_root);
        if (! is_string($build_real)) {
            throw new RuntimeException('Unable to resolve the build directory.');
        }
        $build_real = rtrim(str_replace('\\', '/', $build_real), '/');
        if (! hash_equals($root . '/build', $build_real)) {
            throw new RuntimeException('The build directory resolved outside the repository root.');
        }

        $segments = explode('/', substr($output_dir, strlen('build/')));
        $candidate = $build_real;
        foreach ($segments as $segment) {
            $candidate .= '/' . $segment;
            if (is_link($candidate)) {
                throw new RuntimeException('Output path ancestor may not be a symbolic link.');
            }
            if (file_exists($candidate) && ! is_dir($candidate)) {
                throw new RuntimeException('Output path ancestor must be a directory.');
            }
        }

        if (! self::path_is_within($candidate, $build_real)) {
            throw new RuntimeException('Output directory resolved outside build/.');
        }

        return $candidate;
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

    /** @param array<string,mixed> $stat */
    private static function zip_entry_is_symlink(array $stat): bool
    {
        if (! isset($stat['external_attributes'])) {
            return false;
        }
        $mode = ((int) $stat['external_attributes'] >> 16) & 0170000;

        return $mode === 0120000;
    }

    private static function path_is_within(string $path, string $parent): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $parent = rtrim(str_replace('\\', '/', $parent), '/');

        return $path !== $parent && str_starts_with($path, $parent . '/');
    }

    private static function write_file(string $path, string $contents, int $timestamp): void
    {
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write staging evidence file: ' . basename($path));
        }
        chmod($path, 0644);
        touch($path, $timestamp);
    }

    private static function remove_tree(string $path, string $build_root): void
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $build_root = rtrim(str_replace('\\', '/', $build_root), '/');
        if (! self::path_is_within($path, $build_root)) {
            throw new RuntimeException('Refusing to remove a path outside build/.');
        }
        if (! file_exists($path) && ! is_link($path)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            throw new RuntimeException('Refusing to remove a symbolic link or file as the output directory.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item_path = str_replace('\\', '/', $item->getPathname());
            if (! self::path_is_within($item_path, $path)) {
                throw new RuntimeException('Refusing to remove an item outside the output directory.');
            }
            if ($item->isDir() && ! $item->isLink()) {
                if (! rmdir($item_path)) {
                    throw new RuntimeException('Unable to remove an output directory.');
                }
            } elseif (! unlink($item_path)) {
                throw new RuntimeException('Unable to remove an output file.');
            }
        }
        if (! rmdir($path)) {
            throw new RuntimeException('Unable to remove the previous output directory.');
        }
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
