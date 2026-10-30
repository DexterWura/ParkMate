<?php

namespace App\Domains\Install\Services;

use RuntimeException;

class InstallationState
{
    public readonly string $markerPath;

    public readonly string $lockPath;

    public function __construct()
    {
        $this->markerPath = storage_path('app/installed');
        $this->lockPath = storage_path('app/install.lock');
    }

    public function isInstalled(): bool
    {
        if (app()->runningUnitTests()) {
            return (bool) config('installer.testing_installed', true);
        }

        $markerExists = is_file($this->markerPath);

        return $markerExists;
    }

    public function markInstalled(): void
    {
        $marker = $this->markerPath;
        $temporary = $marker.'.'.bin2hex(random_bytes(6)).'.tmp';

        if (file_put_contents($temporary, now()->toIso8601String().PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('The installation marker could not be written.');
        }

        if (! rename($temporary, $marker)) {
            @unlink($temporary);

            throw new RuntimeException('The installation marker could not be finalized.');
        }
    }
}
