<?php

namespace Tests\Feature\Install;

use App\Domains\Install\DTOs\InstallDTO;
use App\Domains\Install\Services\DatabaseProvisioner;
use App\Domains\Install\Services\EnvironmentWriter;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    private string $temporaryStorage;

    private string $sqliteDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['installer.testing_installed' => false]);

        $this->temporaryStorage = storage_path('framework/testing/install-'.Str::random(10));
        $this->sqliteDatabase = $this->temporaryStorage.'/database.sqlite';
        config(['installer.sqlite_database' => $this->sqliteDatabase]);
        $this->createInstallerDirectories();
        $this->app->useStoragePath($this->temporaryStorage);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        if (is_file($this->sqliteDatabase)) {
            @unlink($this->sqliteDatabase);
        }

        $this->deleteDirectory($this->temporaryStorage);

        parent::tearDown();
    }

    public function test_browser_requests_are_redirected_until_installed(): void
    {
        $this->get('/')
            ->assertRedirect('/install');
    }

    public function test_api_requests_return_service_unavailable_until_installed(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('message', 'The application has not been installed.');
    }

    public function test_installer_is_available_before_installation(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('Install OpenLot')
            ->assertSee('Server preflight')
            ->assertSee('Admin account')
            ->assertSee('/css/openlot.css', false);
    }

    public function test_installer_is_hidden_after_installation(): void
    {
        config(['installer.testing_installed' => true]);

        $this->get('/install')
            ->assertNotFound();
    }

    public function test_install_request_validates_input_without_flashing_secrets(): void
    {
        $this->from('/install')->post('/install', [
            'app_name' => '',
            'app_url' => 'not-a-url',
            'db_connection' => 'mysql',
            'db_password' => 'database-secret',
            'owner_password' => 'owner-secret',
            'owner_password_confirmation' => 'different',
        ])
            ->assertRedirect('/install')
            ->assertSessionHasErrors([
                'app_name',
                'app_url',
                'db_host',
                'db_port',
                'db_database',
                'db_username',
                'owner_name',
                'owner_email',
                'owner_password',
            ])
            ->assertSessionMissing('_old_input.db_password')
            ->assertSessionMissing('_old_input.owner_password')
            ->assertSessionMissing('_old_input.owner_password_confirmation');
    }

    public function test_mysql_database_identifier_is_strictly_validated(): void
    {
        $this->from('/install')->post('/install', [
            'app_name' => 'OpenLot',
            'app_url' => 'https://openlot.test',
            'db_connection' => 'mysql',
            'db_host' => '127.0.0.1',
            'db_port' => 3306,
            'db_database' => 'openlot-production',
            'db_username' => 'openlot',
            'owner_name' => 'Site Owner',
            'owner_email' => 'owner@example.com',
            'owner_password' => 'StrongPassword123!',
            'owner_password_confirmation' => 'StrongPassword123!',
        ])
            ->assertRedirect('/install')
            ->assertSessionHasErrors('db_database');
    }

    public function test_sqlite_install_runs_migrations_creates_owner_and_locks_installer(): void
    {
        $environment = new class extends EnvironmentWriter
        {
            /** @var array<string, string> */
            public array $written = [];

            public function write(array $values): void
            {
                $this->written = [...$this->written, ...$values];
            }
        };
        $this->app->instance(EnvironmentWriter::class, $environment);

        $response = $this->post('/install', [
            'app_name' => 'OpenLot',
            'app_url' => 'https://openlot.companyop.com',
            'db_connection' => 'sqlite',
            'owner_name' => 'Site Owner',
            'owner_email' => 'owner@example.com',
            'owner_password' => 'StrongPassword123!',
            'owner_password_confirmation' => 'StrongPassword123!',
        ]);

        $response
            ->assertOk()
            ->assertSee('Ready to go')
            ->assertSee('Open admin login');

        $this->assertFileExists($this->temporaryStorage.'/app/installed');
        $this->assertSame('sqlite', $environment->written['DB_CONNECTION']);
        $this->assertSame('database', $environment->written['SESSION_DRIVER']);
        $this->assertSame('sync', $environment->written['QUEUE_CONNECTION']);
        $this->assertSame('true', $environment->written['FORCE_HTTPS']);
        $this->assertDatabaseHas('users', [
            'name' => 'Site Owner',
            'email' => 'owner@example.com',
            'role' => 'admin',
        ]);
        $owner = User::query()->where('email', 'owner@example.com')->first();
        $this->assertNotNull($owner?->email_verified_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('StrongPassword123!', $owner->password));
        $this->assertDatabaseHas('parking_spots', ['id' => 'seed-samora-machel']);
        $this->assertDatabaseHas('app_settings', ['key' => 'mapbox_token']);
        $this->assertDatabaseHas('fee_rules', ['id' => 'fee-harare-on-street']);
    }

    public function test_failed_install_does_not_write_marker_or_reflash_passwords(): void
    {
        $environment = new class extends EnvironmentWriter
        {
            /** @var array<string, string> */
            public array $written = [];

            public function write(array $values): void
            {
                $this->written = $values;
            }
        };
        $database = new class extends DatabaseProvisioner
        {
            public function provision(InstallDTO $installation): void
            {
                throw new RuntimeException('The database connection could not be established.');
            }
        };
        $this->app->instance(EnvironmentWriter::class, $environment);
        $this->app->instance(DatabaseProvisioner::class, $database);

        $this->from('/install')->post('/install', [
            'app_name' => 'Failed OpenLot',
            'app_url' => 'https://openlot.test',
            'db_connection' => 'sqlite',
            'owner_name' => 'Site Owner',
            'owner_email' => 'owner@example.com',
            'owner_password' => 'StrongPassword123!',
            'owner_password_confirmation' => 'StrongPassword123!',
        ])
            ->assertRedirect('/install')
            ->assertSessionHas('install_error', 'The database connection could not be established.')
            ->assertSessionMissing('_old_input.owner_password')
            ->assertSessionMissing('_old_input.owner_password_confirmation');

        $this->assertFileDoesNotExist($this->temporaryStorage.'/app/installed');
    }

    private function createInstallerDirectories(): void
    {
        foreach (['app', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
            $path = $this->temporaryStorage.'/'.$directory;

            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $itemPath = $path.DIRECTORY_SEPARATOR.$item;

            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($path);
    }
}
