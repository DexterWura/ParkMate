<?php

namespace App\Domains\Install\Services;

use App\Domains\Install\DTOs\InstallDTO;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use RuntimeException;

class DatabaseProvisioner
{
    public function provision(InstallDTO $installation): void
    {
        if ($installation->db_connection === 'sqlite') {
            $this->provisionSqlite();
        } else {
            $this->provisionMysql($installation);
        }

        $this->configureRuntime($installation);

        try {
            DB::connection()->getPdo();
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'The selected database was created but Laravel could not connect to it.',
                previous: $exception
            );
        }
    }

    private function provisionSqlite(): void
    {
        $path = (string) config('installer.sqlite_database', database_path('database.sqlite'));

        if (! is_file($path) && ! @touch($path)) {
            throw new RuntimeException('The SQLite database file could not be created.');
        }

        if (! is_writable($path)) {
            throw new RuntimeException('The SQLite database file is not writable.');
        }
    }

    private function provisionMysql(InstallDTO $installation): void
    {
        $database = (string) $installation->db_database;

        if (preg_match('/\A[A-Za-z_][A-Za-z0-9_]*\z/', $database) !== 1) {
            throw new RuntimeException('The database name is invalid.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $installation->db_host,
            $installation->db_port
        );
        try {
            $pdo = new PDO($dsn, $installation->db_username, $installation->db_password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
            ]);
            $pdo->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $database
            ));
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'MySQL could not be reached or the account cannot create/access the requested database.',
                previous: $exception
            );
        }
    }

    private function configureRuntime(InstallDTO $installation): void
    {
        if ($installation->db_connection === 'sqlite') {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => config(
                    'installer.sqlite_database',
                    database_path('database.sqlite')
                ),
            ]);
            DB::purge('sqlite');

            return;
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $installation->db_host,
            'database.connections.mysql.port' => (string) $installation->db_port,
            'database.connections.mysql.database' => $installation->db_database,
            'database.connections.mysql.username' => $installation->db_username,
            'database.connections.mysql.password' => $installation->db_password,
            'database.connections.mysql.charset' => 'utf8mb4',
            'database.connections.mysql.collation' => 'utf8mb4_unicode_ci',
        ]);
        DB::purge('mysql');
    }
}
