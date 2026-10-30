<?php

namespace App\Domains\User\Repositories;

use App\Domains\User\Interfaces\UserRepositoryInterface;
use App\Domains\User\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function findByUuid(string $uuid): ?User
    {
        return $this->model->newQuery()->where('uuid', $uuid)->first();
    }

    public function createUser(array $attributes): User
    {
        return $this->create($attributes);
    }

    public function verified(): Collection
    {
        return $this->model->newQuery()->whereNotNull('email_verified_at')->get();
    }
}
