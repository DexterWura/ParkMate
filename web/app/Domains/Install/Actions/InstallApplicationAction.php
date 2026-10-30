<?php

namespace App\Domains\Install\Actions;

use App\Domains\Install\DTOs\InstallDTO;
use App\Domains\Install\Services\ApplicationKeyService;
use App\Domains\Install\Services\DatabaseProvisioner;
use App\Domains\Install\Services\EnvironmentWriter;
use App\Domains\Install\Services\InstallationState;
use App\Domains\Install\Services\PreflightService;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InstallApplicationAction
{
    public function __construct(
        private readonly InstallationState $installation,
        private readonly ApplicationKeyService $keys,
        private readonly PreflightService $preflight,
        private readonly EnvironmentWriter $environment,
        private readonly DatabaseProvisioner $databases
    ) {}

    public function execute(InstallDTO $data): void
    {
        $lock = @fopen($this->installation->lockPath, 'c+');

        if ($lock === false) {
            throw new RuntimeException('The installer lock could not be created.');
        }

        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            throw new RuntimeException('Another installation is already in progress.');
        }

        try {
            if ($this->installation->isInstalled()) {
                throw new RuntimeException('The application is already installed.');
            }

            $preflight = $this->preflight->inspect($data->db_connection);

            if (! $preflight['ready']) {
                throw new RuntimeException('The server does not meet the installation requirements.');
            }

            $key = $this->keys->ensureRuntimeKey();
            $this->environment->write($this->environmentValues($data, $key));
            $this->keys->removeTemporaryKey();
            Artisan::call('config:clear');

            config([
                'app.name' => $data->app_name,
                'app.url' => $data->app_url,
                'app.key' => $key,
            ]);

            $this->databases->provision($data);
            $migrationExitCode = Artisan::call('migrate', ['--force' => true]);

            if ($migrationExitCode !== 0) {
                throw new RuntimeException('The database migrations could not be completed.');
            }

            $this->createOrRecoverOwner($data);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ParkMateSeeder', '--force' => true]);
            $this->environment->write([
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'database',
                'QUEUE_CONNECTION' => 'database',
            ]);
            $this->installation->markInstalled();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array<string, string>
     */
    private function environmentValues(InstallDTO $data, string $key): array
    {
        $mysql = $data->db_connection !== 'sqlite';

        return [
            'APP_NAME' => $data->app_name,
            'APP_ENV' => 'production',
            'APP_URL' => $data->app_url,
            'APP_KEY' => $key,
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => $mysql ? 'mysql' : 'sqlite',
            'DB_HOST' => $mysql ? (string) $data->db_host : '',
            'DB_PORT' => $mysql ? (string) $data->db_port : '',
            'DB_DATABASE' => $mysql
                ? (string) $data->db_database
                : str_replace('\\', '/', (string) config(
                    'installer.sqlite_database',
                    database_path('database.sqlite')
                )),
            'DB_USERNAME' => $mysql ? (string) $data->db_username : '',
            'DB_PASSWORD' => $mysql ? (string) $data->db_password : '',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ];
    }

    private function createOrRecoverOwner(InstallDTO $data): void
    {
        $owner = User::query()->where('email', $data->owner_email)->first();

        if ($owner === null) {
            if (User::query()->exists()) {
                throw new RuntimeException('The database already contains a different user.');
            }

            // Create the owner directly — do not fire UserRegistered / verification
            // mail during install (verification routes may be API-only, and the
            // installer marks this account verified immediately).
            User::query()->create([
                'name' => $data->owner_name,
                'email' => $data->owner_email,
                'password' => Hash::make($data->owner_password),
                'email_verified_at' => now(),
                'role' => 'admin',
                'is_active' => true,
            ]);

            return;
        }

        if ($owner->name !== $data->owner_name || ! Hash::check($data->owner_password, $owner->password)) {
            throw new RuntimeException('The owner account conflicts with an existing user.');
        }

        $owner->forceFill([
            'email_verified_at' => $owner->email_verified_at ?? now(),
            'role' => 'admin',
            'is_active' => true,
        ])->save();
    }
}
