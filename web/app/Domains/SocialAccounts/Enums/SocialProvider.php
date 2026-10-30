<?php

namespace App\Domains\SocialAccounts\Enums;

enum SocialProvider: string
{
    case Google = 'google';

    public function isSupported(): bool
    {
        return in_array($this, self::cases(), true);
    }
}
