<?php

namespace App\Domains\Install\Services;

class ApplicationKeyService
{
    public function ensureRuntimeKey(): string
    {
        $configured = (string) config('app.key');

        if ($configured !== '') {
            return $configured;
        }

        if (app()->runningUnitTests()) {
            $key = 'base64:'.base64_encode(str_repeat('t', 32));
            config(['app.key' => $key]);

            return $key;
        }

        $keyPath = storage_path('app/install.key');
        $stored = is_file($keyPath) ? trim((string) @file_get_contents($keyPath)) : '';
        $decoded = str_starts_with($stored, 'base64:')
            ? base64_decode(substr($stored, 7), true)
            : false;
        $storedIsValid = is_string($decoded) && strlen($decoded) === 32;
        $key = $storedIsValid ? $stored : 'base64:'.base64_encode(random_bytes(32));

        if (! $storedIsValid) {
            $this->persistTemporaryKey($keyPath, $key);
        }

        config(['app.key' => $key]);

        return $key;
    }

    public function removeTemporaryKey(): void
    {
        $keyPath = storage_path('app/install.key');

        if (is_file($keyPath)) {
            @unlink($keyPath);
        }
    }

    private function persistTemporaryKey(string $path, string $key): void
    {
        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';

        if (@file_put_contents($temporary, $key.PHP_EOL, LOCK_EX) === false) {
            return;
        }

        if (! @rename($temporary, $path)) {
            @unlink($temporary);

            return;
        }

        @chmod($path, 0600);
    }
}
