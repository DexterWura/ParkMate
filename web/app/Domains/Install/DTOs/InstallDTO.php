<?php

namespace App\Domains\Install\DTOs;

use App\Shared\DTOs\BaseDTO;

class InstallDTO extends BaseDTO
{
    public string $app_name;

    public string $app_url;

    public string $db_connection;

    public ?string $db_host = null;

    public int|string|null $db_port = null;

    public ?string $db_database = null;

    public ?string $db_username = null;

    public ?string $db_password = null;

    public string $owner_name;

    public string $owner_email;

    public string $owner_password;
}
