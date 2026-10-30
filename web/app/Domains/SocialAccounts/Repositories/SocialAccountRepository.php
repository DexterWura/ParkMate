<?php

namespace App\Domains\SocialAccounts\Repositories;

use App\Domains\SocialAccounts\Interfaces\SocialAccountRepositoryInterface;
use App\Domains\SocialAccounts\Models\SocialAccount;
use App\Shared\Repositories\BaseRepository;

class SocialAccountRepository extends BaseRepository implements SocialAccountRepositoryInterface
{
    public function __construct(SocialAccount $model)
    {
        parent::__construct($model);
    }

    public function findByProvider(string $provider, string $providerId): ?SocialAccount
    {
        return $this->model->newQuery()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    public function createForUser(int $userId, array $attributes): SocialAccount
    {
        return $this->model->newQuery()->create(array_merge($attributes, [
            'user_id' => $userId,
        ]));
    }

    public function updateTokens(SocialAccount $account, array $attributes): SocialAccount
    {
        $account->update($attributes);

        return $account->fresh();
    }
}
