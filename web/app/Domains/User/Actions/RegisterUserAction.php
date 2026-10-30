<?php

namespace App\Domains\User\Actions;

use App\Core\Exceptions\DomainException;
use App\Domains\User\DTOs\RegisterUserDTO;
use App\Domains\User\Events\UserRegistered;
use App\Domains\User\Interfaces\UserRepositoryInterface;
use App\Domains\User\Models\User;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction extends BaseAction
{
    public function __construct(
        protected UserRepositoryInterface $users
    ) {}

    public function execute(RegisterUserDTO $dto): User
    {
        if ($this->users->findByEmail($dto->email)) {
            throw new DomainException('A user with this email already exists.', 409);
        }

        $user = $this->users->createUser([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
        ]);

        event(new UserRegistered($user));

        return $user;
    }
}
