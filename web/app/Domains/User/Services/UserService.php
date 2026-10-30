<?php

namespace App\Domains\User\Services;

use App\Domains\User\Interfaces\UserRepositoryInterface;
use App\Domains\User\Models\User;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $users
    ) {}

    public function getProfile(User $user): User
    {
        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        return $this->users->update($user, $data);
    }

    public function changePassword(User $user, string $password): User
    {
        return $this->users->update($user, ['password' => $password]);
    }
}
