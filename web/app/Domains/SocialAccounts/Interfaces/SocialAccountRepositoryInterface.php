<?php

namespace App\Domains\SocialAccounts\Interfaces;

use App\Domains\SocialAccounts\Models\SocialAccount;

interface SocialAccountRepositoryInterface
{
    public function findByProvider(string $provider, string $providerId): ?SocialAccount;

    public function createForUser(int $userId, array $attributes): SocialAccount;

    public function updateTokens(SocialAccount $account, array $attributes): SocialAccount;
}
