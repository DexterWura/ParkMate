<?php

namespace App\Domains\User\DTOs;

use App\Shared\DTOs\BaseDTO;

class LoginUserDTO extends BaseDTO
{
    public string $email;

    public string $password;

    public ?string $device_name = 'api';
}
