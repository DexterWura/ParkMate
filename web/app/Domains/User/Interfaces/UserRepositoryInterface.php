<?php

namespace App\Domains\User\Interfaces;

use App\Domains\User\Models\User;
use App\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByUuid(string $uuid): ?User;

    public function createUser(array $attributes): User;

    public function verified(): Collection;
}
