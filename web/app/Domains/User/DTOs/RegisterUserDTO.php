<?php

namespace App\Domains\User\DTOs;

use App\Shared\DTOs\BaseDTO;

class RegisterUserDTO extends BaseDTO
{
    public string $name;

    public string $email;

    public string $password;
}
