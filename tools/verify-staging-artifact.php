<?php

declare(strict_types=1);

/**
 * Independent verifier for File 25 workflow artifacts and assembled directories.
 *
 * This verifier does not trust the builder, detached files, outer archive, or
 * inner package individually. Every layer is reconciled before a candidate is
 * reported as verified. Verification never implies staging or production acceptance.
 */
final class File25_Staging_Artifact_Verifier
{
    private const PACKAGE_ROOT = 'sabri-public-experience';
    private const INNER_MANIFEST = 'STAGING-MANIFEST.json';
    private const INNER_MATRIX = 'config/staging-dependencies.json';
    private const MAX_OUTER_FILES = 8;
    private const MAX_INNER_FILES = 256;
    private const MAX_OUTER_FILE_BYTES = 30_000_000;
    private const MAX_INNER_FILE_BYTES = 5_000_000;
    private const MAX_INNER_TOTAL_BYTES = 25_000_000;

    /** @return array<string,mixed> */
    public static function verify(string $artifact, string $expected_outer_sha256 = ''): array
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The PHP Zip extension is required to verify the staging artifact.');
        }

        $artifact = trim($artifact);
        if ($artifact === '' || str_contains($artifact, "\0") || is_link($artifact)) {
            throw new InvalidArgumentException('Artifact path is invalid or symbolic.');
        }

        $outer_artifact_sha256 = '';
        if (is_dir($artifact)) {
            $files = self::read_artifact_directory($artifact);
            $mode = 'directory';
        } elseif (is_file($artifact)) {
            $real = realpath($artifact);
            if (! is_string($real)) {
                throw new RuntimeException('Unable to resolve the artifact file.');
            }
            $outer_artifact_sha256 = (string) hash_file('sha256', $real);
            if (preg_match('/^[a-f0-9]{64}$/', $outer_artifact_sha256) !== 1) {
                throw new RuntimeException('Unable to calculate the outer artifact SHA-256.');
            }
            $expected_outer_sha256 = strtolower(trim($expected_outer_sha256));
            if ($expected_outer_sha256 !== '') {
                if (preg_match('/^[a-f0-9]{64}$/', $expected_outer_sha256) !== 1) {
                    throw new InvalidArgumentException('Expected outer artifact SHA-256 is invalid.');
                }
                if (! hash_equals($expected_outer_sha256, $outer_artifact_sha256)) {
                    throw new RuntimeException('Outer artifact SHA-256 does not match the expected digest.');
                }
            }
            $files = self::read_outer_zip($real);
            $mode = 'zip';
        } else {
            throw new InvalidArgumentException('Artifact path does not exist.');
        }

        $result = self::verify_bundle(self::identify_bundle($files));
        $result['artifact_mode'] = $mode;
        $result['outer_artifact_sha256'] = $outer_artifact_sha256;

        return $result;
    }

    /** @return array<string,string> */
    private static function read_artifact_directory(string $directory): array
    {
        $real = realpath($directory);
        if (! is_string($real) || ! is_dir($real)) {
            throw new RuntimeException('Unable to resolve the artifact directory.');
        }
        $entries = scandir($real);
        if (! is_array($entries)) {
            throw new RuntimeException('Unable to list the artifact directory.');
        }

        $files = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (! self::outer_name_is_safe($entry)) {
                throw new RuntimeException('Unsafe artifact filename: ' . $entry);
            }
            $path = $real . DIRECTORY_SEPARATOR . $entry;
            if (is_link($path) || ! is_file($path)) {
                throw new RuntimeException('Artifact directory may contain regular files only.');
            }
            $bytes = filesize($path);
            if (! is_int($bytes) || $bytes < 0 || $bytes > self::MAX_OUTER_FILE_BYTES) {
                throw new RuntimeException('Artifact file exceeds the permitted size: ' . $entry);
            }
            $contents = file_get_contents($path);
            if (! is_string($contents) || strlen($contents) !== $bytes) {
                throw new RuntimeException('Unable to read artifact file: ' . $entry);
            }
            if (isset($files[$entry])) {
                throw new RuntimeException('Duplicate entry detected in artifact directory: ' . $entry);
            }
            $files[$entry] = $contents;
        }
        if ($files === [] || count($files) > self::MAX_OUTER_FILES) {
            throw new RuntimeException('Artifact directory contains an invalid number of files.');
        }

        return $files;
    }

    /** @return array<string,string> */
    private static function read_outer_zip(string $zip_path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Unable to open the downloaded workflow artifact.');
        }
        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_OUTER_FILES) {
                throw new RuntimeException('Workflow artifact contains an invalid number of entries.');
            }
            $files = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                $stat = $zip->statIndex($index);
                if (! is_string($name) || ! is_array($stat) || ! self::outer_name_is_safe($name)) {
                    throw new RuntimeException('Unsafe entry detected in the downloaded workflow artifact.');
                }
                if (isset($files[$name])) {
                    throw new RuntimeException('Duplicate entry detected in the downloaded workflow artifact: ' . $name);
                }
                if (self::zip_entry_is_symlink($stat)) {
                    throw new RuntimeException('Symbolic link detected in the downloaded workflow artifact.');
                }
                $bytes = (int) ($stat['size'] ?? -1);
                if ($bytes < 0 || $bytes > self::MAX_OUTER_FILE_BYTES) {
                    throw new RuntimeException('Workflow artifact entry exceeds the permitted size: ' . $name);
                }
                $contents = $zip->getFromIndex($index);
                if (! is_string($contents) || strlen($contents) !== $bytes) {
                    throw new RuntimeException('Unable to read workflow artifact entry: ' . $name);
                }
                $files[$name] = $contents;
            }

            return $files;
        } finally {
            $zip->close();
        }
    }

    /** @param array<string,string> $files @return array<string,string> */
    private static function identify_bundle(array $files): array
    {
        $plugin_zips = [];
        foreach (array_keys($files) as $name) {
            if (preg_match('/^sabri-public-experience-(\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?)\.zip$/', $name, $match) === 1) {
                $plugin_zips[$name] = $match[1];
            }
        }
        if (count($plugin_zips) !== 1) {
            throw new RuntimeException('Artifact must contain exactly one versioned File 25 plugin ZIP.');
        }

        $zip_name = (string) array_key_first($plugin_zips);
        $version = (string) $plugin_zips[$zip_name];
        $expected = [
            $zip_name,
            'sabri-public-experience-' . $version . '.sha256',
            'sabri-public-experience-' . $version . '-manifest.json',
            'staging-dependencies.json',
        ];
        sort($expected, SORT_STRING);
        $actual = array_keys($files);
        sort($actual, SORT_STRING);
        if ($actual !== $expected) {
            throw new RuntimeException('Artifact file set does not match the governed staging bundle.');
        }

        return [
            'version' => $version,
            'zip_name' => $zip_name,
            'zip_bytes' => $files[$zip_name],
            'checksum_bytes' => $files['sabri-public-experience-' . $version . '.sha256'],
            'manifest_bytes' => $files['sabri-public-experience-' . $version . '-manifest.json'],
            'matrix_bytes' => $files['staging-dependencies.json'],
        ];
    }

    /** @param array<string,string> $bundle @return array<string,mixed> */
    private static function verify_bundle(array $bundle): array
    {
        $version = $bundle['version'];
        $zip_name = $bundle['zip_name'];
        $zip_bytes = $bundle['zip_bytes'];
        $checksum_bytes = $bundle['checksum_bytes'];
        $manifest_bytes = $bundle['manifest_bytes'];
        $matrix_bytes = $bundle['matrix_bytes'];

        if (preg_match('/^([a-f0-9]{64})  ([A-Za-z0-9._+-]+\.zip)\n?$/', $checksum_bytes, $match) !== 1) {
            throw new RuntimeException('Detached package checksum has an invalid format.');
        }
        if (! hash_equals($zip_name, $match[2])) {
            throw new RuntimeException('Detached checksum refers to a different package filename.');
        }
        $package_sha256 = hash('sha256', $zip_bytes);
        if (! hash_equals(strtolower($match[1]), $package_sha256)) {
            throw new RuntimeException('Plugin ZIP SHA-256 does not match the detached checksum.');
        }

        $manifest = self::decode_json_object($manifest_bytes, 'detached manifest');
        self::validate_manifest($manifest, $version);
        $matrix = self::decode_json_object($matrix_bytes, 'dependency matrix');
        $file24_status = self::validate_matrix($matrix, $version);
        $inner = self::verify_inner_zip($zip_bytes, $manifest_bytes, $manifest, $matrix_bytes);

        return [
            'verified' => true,
            'version' => $version,
            'commit_sha' => (string) $manifest['commit_sha'],
            'package_sha256' => $package_sha256,
            'payload_file_count' => $inner['payload_file_count'],
            'payload_bytes' => $inner['payload_bytes'],
            'file24_status' => $file24_status,
            'staging_accepted' => false,
            'production_accepted' => false,
        ];
    }

    /** @param array<string,mixed> $manifest */
    private static function validate_manifest(array $manifest, string $version): void
    {
        if (($manifest['schema_version'] ?? null) !== 1
            || ($manifest['package'] ?? '') !== self::PACKAGE_ROOT
            || ($manifest['file_number'] ?? null) !== 25
            || ($manifest['version'] ?? '') !== $version
        ) {
            throw new RuntimeException('Detached manifest identity does not match File 25.');
        }
        if (preg_match('/^[a-f0-9]{40}$/', (string) ($manifest['commit_sha'] ?? '')) !== 1) {
            throw new RuntimeException('Detached manifest commit SHA is invalid.');
        }
        $epoch = $manifest['source_date_epoch'] ?? null;
        if (! is_int($epoch) || $epoch < 315532800 || $epoch > 4102444800) {
            throw new RuntimeException('Detached manifest SOURCE_DATE_EPOCH is invalid.');
        }
        if (($manifest['generated_at_utc'] ?? '') !== gmdate('Y-m-d\TH:i:s\Z', $epoch)) {
            throw new RuntimeException('Detached manifest timestamp is not derived from SOURCE_DATE_EPOCH.');
        }
        $files = $manifest['files'] ?? null;
        if (! is_array($files) || $files === [] || count($files) > self::MAX_INNER_FILES) {
            throw new RuntimeException('Detached manifest contains an invalid payload file map.');
        }
        foreach ($files as $relative => $metadata) {
            if (! is_string($relative) || ! self::relative_payload_name_is_safe($relative) || ! is_array($metadata)) {
                throw new RuntimeException('Detached manifest contains an unsafe payload entry.');
            }
            if (preg_match('/^[a-f0-9]{64}$/', (string) ($metadata['sha256'] ?? '')) !== 1) {
                throw new RuntimeException('Detached manifest contains an invalid payload SHA-256.');
            }
            $bytes = $metadata['bytes'] ?? null;
            if (! is_int($bytes) || $bytes < 0 || $bytes > self::MAX_INNER_FILE_BYTES) {
                throw new RuntimeException('Detached manifest contains an invalid payload byte size.');
            }
        }
    }

    /** @param array<string,mixed> $matrix */
    private static function validate_matrix(array $matrix, string $version): string
    {
        if (($matrix['schema_version'] ?? null) !== 2
            || ($matrix['file'] ?? null) !== 25
            || ($matrix['runtime_version'] ?? '') !== $version
            || ($matrix['governing_sources']['platform_master_plan'] ?? '') !== 'Sabri Social Homeopathy Platform Definitive Master Plan 2026 v3.0'
            || ($matrix['governing_sources']['file_20_plan'] ?? '') !== 'File 20 Harmonized Master Plan 2026 v4.1'
            || ($matrix['environment']['target_site'] ?? '') !== 'https://sabrisocialstaging.sabrihomeopathy.com/'
            || ($matrix['environment']['live_changes_allowed'] ?? true) !== false
            || ($matrix['environment']['registration_disabled_required'] ?? false) !== true
            || ($matrix['environment']['search_indexing_disabled_required'] ?? false) !== true
        ) {
            throw new RuntimeException('Staging dependency matrix identity or environment policy is invalid.');
        }

        $modules = [];
        foreach ((array) ($matrix['modules'] ?? []) as $module) {
            if (is_array($module) && is_int($module['file'] ?? null)) {
                if (isset($modules[$module['file']])) {
                    throw new RuntimeException('Staging dependency matrix contains a duplicate File number.');
                }
                $modules[$module['file']] = $module;
            }
        }
        foreach ([0, 3, 6, 8, 9, 10, 11, 12, 18, 20, 21, 24, 25] as $required) {
            if (! isset($modules[$required])) {
                throw new RuntimeException('Staging dependency matrix is missing File ' . $required . '.');
            }
        }

        $file00 = $modules[0];
        if (($file00['reviewed_package_version'] ?? '') !== '1.2.4'
            || ($file00['accepted_source_range'] ?? '') !== '>=1.2.4 <1.3.0'
            || ($file00['required_contract_version'] ?? '') !== '1.1.2'
            || ($file00['foreign_table_reads_allowed'] ?? true) !== false
            || ($file00['staging_status'] ?? '') !== 'pending'
        ) {
            throw new RuntimeException('File 00 reviewed contract or pending staging state is inaccurate.');
        }
        if (($modules[8]['required_public_contract_version'] ?? '') !== '1.0.0'
            || ($modules[8]['foreign_table_reads_allowed'] ?? true) !== false
            || ($modules[8]['staging_status'] ?? '') !== 'pending'
        ) {
            throw new RuntimeException('File 08 public clinic contract or pending state is inaccurate.');
        }
        if (($modules[9]['reviewed_source_version'] ?? '') !== '1.1.0'
            || ($modules[9]['foreign_table_reads_allowed'] ?? true) !== false
            || ($modules[9]['staging_status'] ?? '') !== 'pending'
        ) {
            throw new RuntimeException('File 09 verification contract or pending state is inaccurate.');
        }
        if (($modules[18]['reviewed_source_version'] ?? '') !== '1.2.0-RC1'
            || ($modules[18]['foreign_table_reads_allowed'] ?? true) !== false
            || ($modules[18]['staging_status'] ?? '') !== 'pending'
        ) {
            throw new RuntimeException('File 18 owner-DTO contract or pending state is inaccurate.');
        }
        if (($modules[20]['reviewed_source_version'] ?? '') !== '1.2.0'
            || ($modules[20]['governing_plan_version'] ?? '') !== '4.1'
            || ($modules[20]['staging_status'] ?? '') !== 'pending'
        ) {
            throw new RuntimeException('File 20 shell contract or pending state is inaccurate.');
        }

        $file24 = $modules[24];
        if (($file24['reviewed_package_version'] ?? '') !== '0.25.3'
            || ($file24['accepted_source_range'] ?? '') !== '>=0.25.3 <0.26.0'
            || ($file24['accepted_runtime_contract'] ?? '') !== 'reviewed-source-contract-pending-staging'
            || ($file24['cache_partition_contract'] ?? '') !== 'not-yet-versioned'
            || ($file24['staging_status'] ?? '') !== 'pending'
        ) {
            throw new RuntimeException('File 24 reviewed contract or pending staging state is inaccurate.');
        }
        if (($modules[25]['candidate_version'] ?? '') !== $version
            || ($modules[25]['schema_version'] ?? '') !== '2'
            || ($modules[25]['staging_status'] ?? '') !== 'pending'
            || ($modules[25]['package_status'] ?? '') !== 'build-input-not-acceptance'
        ) {
            throw new RuntimeException('File 25 dependency-matrix candidate status is inaccurate.');
        }

        return 'reviewed-source-contract-pending-staging';
    }

    /** @param array<string,mixed> $manifest @return array{payload_file_count:int,payload_bytes:int} */
    private static function verify_inner_zip(string $zip_bytes, string $manifest_bytes, array $manifest, string $matrix_bytes): array
    {
        $temporary = tempnam(sys_get_temp_dir(), 'file25-');
        if (! is_string($temporary)) {
            throw new RuntimeException('Unable to create a temporary package file.');
        }
        try {
            if (file_put_contents($temporary, $zip_bytes, LOCK_EX) !== strlen($zip_bytes)) {
                throw new RuntimeException('Unable to write the temporary package file.');
            }
            $zip = new ZipArchive();
            if ($zip->open($temporary, ZipArchive::RDONLY) !== true) {
                throw new RuntimeException('Unable to open the inner File 25 plugin ZIP.');
            }
            try {
                $expected = [];
                foreach (array_keys((array) $manifest['files']) as $relative) {
                    $expected[] = self::PACKAGE_ROOT . '/' . $relative;
                }
                $expected[] = self::PACKAGE_ROOT . '/' . self::INNER_MANIFEST;
                sort($expected, SORT_STRING);
                if ($zip->numFiles !== count($expected) || $zip->numFiles > self::MAX_INNER_FILES + 1) {
                    throw new RuntimeException('Inner plugin ZIP contains an invalid number of entries.');
                }

                $actual = [];
                $seen = [];
                $total_bytes = 0;
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = $zip->getNameIndex($index);
                    $stat = $zip->statIndex($index);
                    if (! is_string($name) || ! is_array($stat) || ! self::inner_name_is_safe($name)) {
                        throw new RuntimeException('Unsafe entry detected in the inner plugin ZIP.');
                    }
                    if (isset($seen[$name])) {
                        throw new RuntimeException('Duplicate entry detected in the inner plugin ZIP: ' . $name);
                    }
                    $seen[$name] = true;
                    if (self::zip_entry_is_symlink($stat)) {
                        throw new RuntimeException('Symbolic link detected in the inner plugin ZIP.');
                    }
                    $bytes = (int) ($stat['size'] ?? -1);
                    if ($bytes < 0 || $bytes > self::MAX_INNER_FILE_BYTES) {
                        throw new RuntimeException('Inner plugin ZIP entry exceeds the permitted size: ' . $name);
                    }
                    $total_bytes += $bytes;
                    if ($total_bytes > self::MAX_INNER_TOTAL_BYTES) {
                        throw new RuntimeException('Inner plugin ZIP exceeds the permitted total payload size.');
                    }
                    $actual[] = $name;
                }
                sort($actual, SORT_STRING);
                if ($actual !== $expected) {
                    throw new RuntimeException('Inner plugin ZIP file set differs from the detached manifest.');
                }

                $embedded_manifest = $zip->getFromName(self::PACKAGE_ROOT . '/' . self::INNER_MANIFEST);
                if (! is_string($embedded_manifest) || ! hash_equals($manifest_bytes, $embedded_manifest)) {
                    throw new RuntimeException('Embedded and detached staging manifests differ.');
                }
                $embedded_matrix = $zip->getFromName(self::PACKAGE_ROOT . '/' . self::INNER_MATRIX);
                if (! is_string($embedded_matrix) || ! hash_equals($matrix_bytes, $embedded_matrix)) {
                    throw new RuntimeException('Outer and embedded dependency matrices differ.');
                }

                $payload_bytes = 0;
                foreach ((array) $manifest['files'] as $relative => $metadata) {
                    $name = self::PACKAGE_ROOT . '/' . $relative;
                    $contents = $zip->getFromName($name);
                    if (! is_string($contents)) {
                        throw new RuntimeException('Manifest payload file is missing from the inner ZIP: ' . $relative);
                    }
                    $bytes = strlen($contents);
                    if ($bytes !== (int) $metadata['bytes']) {
                        throw new RuntimeException('Payload byte size differs from the manifest: ' . $relative);
                    }
                    if (! hash_equals((string) $metadata['sha256'], hash('sha256', $contents))) {
                        throw new RuntimeException('Payload SHA-256 differs from the manifest: ' . $relative);
                    }
                    $payload_bytes += $bytes;
                }

                return [
                    'payload_file_count' => count((array) $manifest['files']),
                    'payload_bytes' => $payload_bytes,
                ];
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($temporary);
        }
    }

    /** @return array<string,mixed> */
    private static function decode_json_object(string $json, string $label): array
    {
        try {
            $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to decode ' . $label . ': ' . $exception->getMessage());
        }
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException(ucfirst($label) . ' must be a JSON object.');
        }

        return $decoded;
    }

    private static function outer_name_is_safe(string $name): bool
    {
        return $name !== ''
            && strlen($name) <= 180
            && basename($name) === $name
            && ! str_contains($name, '..')
            && ! str_contains($name, "\0")
            && preg_match('/^[A-Za-z0-9._+-]+$/', $name) === 1;
    }

    private static function relative_payload_name_is_safe(string $name): bool
    {
        return $name !== ''
            && strlen($name) <= 240
            && ! str_starts_with($name, '/')
            && ! str_contains($name, '..')
            && ! str_contains($name, '\\')
            && ! str_contains($name, "\0")
            && preg_match('#^[A-Za-z0-9._/+-]+$#', $name) === 1;
    }

    private static function inner_name_is_safe(string $name): bool
    {
        return str_starts_with($name, self::PACKAGE_ROOT . '/')
            && self::relative_payload_name_is_safe(substr($name, strlen(self::PACKAGE_ROOT) + 1));
    }

    /** @param array<string,mixed> $stat */
    private static function zip_entry_is_symlink(array $stat): bool
    {
        $attributes = (int) ($stat['external_attributes'] ?? $stat['externalAttributes'] ?? 0);
        $mode = ($attributes >> 16) & 0xF000;

        return $mode === 0xA000;
    }
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $artifact = '';
    $expected = '';
    foreach (array_slice($argv ?? [], 1) as $argument) {
        if (str_starts_with($argument, '--artifact=')) {
            $artifact = substr($argument, strlen('--artifact='));
        } elseif (str_starts_with($argument, '--artifact-sha256=')) {
            $expected = substr($argument, strlen('--artifact-sha256='));
        }
    }

    try {
        $report = File25_Staging_Artifact_Verifier::verify($artifact, $expected);
        echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
    } catch (Throwable $exception) {
        fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}
