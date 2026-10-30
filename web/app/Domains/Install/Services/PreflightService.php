<?php

namespace App\Domains\Install\Services;

class PreflightService
{
    /**
     * @return array{ready: bool, checks: array<int, array{key: string, label: string, passed: bool, required: bool, message: string}>}
     */
    public function inspect(?string $connection = null): array
    {
        $drivers = class_exists(\PDO::class) ? \PDO::getAvailableDrivers() : [];
        $requiredExtensions = ['ctype', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pcre', 'session', 'tokenizer', 'xml'];
        $missingExtensions = array_values(array_filter(
            $requiredExtensions,
            fn (string $extension): bool => ! extension_loaded($extension)
        ));
        $environmentIsWritable = $this->environmentPathIsWritable();
        $checks = [
            $this->check('php', 'PHP 8.3 or newer', version_compare(PHP_VERSION, '8.3.0', '>='), true, PHP_VERSION),
            $this->check('pdo', 'PDO extension', extension_loaded('pdo'), true, extension_loaded('pdo') ? 'Available' : 'Missing'),
            $this->check(
                'php_extensions',
                'Required PHP extensions',
                $missingExtensions === [],
                true,
                $missingExtensions === [] ? 'Available' : 'Missing: '.implode(', ', $missingExtensions)
            ),
            $this->check('pdo_sqlite', 'PDO SQLite driver', in_array('sqlite', $drivers, true), $connection === 'sqlite', in_array('sqlite', $drivers, true) ? 'Available' : 'Missing'),
            $this->check('pdo_mysql', 'PDO MySQL/MariaDB driver', in_array('mysql', $drivers, true), in_array($connection, ['mysql', 'mariadb'], true), in_array('mysql', $drivers, true) ? 'Available' : 'Missing'),
            $this->check(
                'environment',
                'Project root and .env',
                $environmentIsWritable,
                true,
                $environmentIsWritable ? 'Writable' : 'Not writable'
            ),
            $this->pathCheck('storage', 'Storage directory', storage_path()),
            $this->pathCheck('storage_app', 'Storage app directory', storage_path('app')),
            $this->pathCheck('storage_framework', 'Storage framework directory', storage_path('framework')),
            $this->pathCheck('storage_logs', 'Storage logs directory', storage_path('logs')),
            $this->pathCheck('bootstrap_cache', 'Bootstrap cache directory', base_path('bootstrap/cache')),
            $connection === 'sqlite'
                ? $this->pathCheck(
                    'database',
                    'Database directory',
                    dirname((string) config('installer.sqlite_database', database_path('database.sqlite')))
                )
                : $this->readablePathCheck('database', 'Database migrations directory', database_path('migrations')),
        ];

        $ready = ! collect($checks)->contains(
            fn (array $check): bool => $check['required'] && ! $check['passed']
        );

        return ['ready' => $ready, 'checks' => $checks];
    }

    private function environmentPathIsWritable(): bool
    {
        $environment = base_path('.env');
        $rootIsWritable = is_writable(base_path());
        $environmentIsWritable = ! is_file($environment) || is_writable($environment);

        return $rootIsWritable && $environmentIsWritable;
    }

    /**
     * @return array{key: string, label: string, passed: bool, required: bool, message: string}
     */
    private function pathCheck(string $key, string $label, string $path): array
    {
        $passed = is_dir($path) && is_writable($path);

        return $this->check($key, $label, $passed, true, $passed ? 'Writable' : 'Not writable');
    }

    /**
     * @return array{key: string, label: string, passed: bool, required: bool, message: string}
     */
    private function readablePathCheck(string $key, string $label, string $path): array
    {
        $passed = is_dir($path) && is_readable($path);

        return $this->check($key, $label, $passed, true, $passed ? 'Readable' : 'Not readable');
    }

    /**
     * @return array{key: string, label: string, passed: bool, required: bool, message: string}
     */
    private function check(
        string $key,
        string $label,
        bool $passed,
        bool $required,
        string $message
    ): array {
        $result = compact('key', 'label', 'passed', 'required', 'message');

        return $result;
    }
}
