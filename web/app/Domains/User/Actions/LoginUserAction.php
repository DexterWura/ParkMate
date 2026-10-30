<?php

namespace App\Domains\User\Actions;

use App\Core\Exceptions\DomainException;
use App\Domains\User\DTOs\LoginUserDTO;
use App\Domains\User\Interfaces\UserRepositoryInterface;
use App\Domains\User\Models\User;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Hash;

class LoginUserAction extends BaseAction
{
    public function __construct(
        protected UserRepositoryInterface $users
    ) {}

    public function execute(LoginUserDTO $dto): array
    {
        $user = $this->users->findByEmail($dto->email);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new DomainException('Invalid credentials.', 401);
        }

        $token = $user->createToken($dto->device_name ?? 'api')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
