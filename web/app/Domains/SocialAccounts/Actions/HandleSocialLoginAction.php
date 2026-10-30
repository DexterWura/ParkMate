<?php

namespace App\Domains\SocialAccounts\Actions;

use App\Domains\SocialAccounts\Enums\SocialProvider;
use App\Domains\SocialAccounts\Interfaces\SocialAccountRepositoryInterface;
use App\Domains\User\Events\UserRegistered;
use App\Domains\User\Interfaces\UserRepositoryInterface;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class HandleSocialLoginAction
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected SocialAccountRepositoryInterface $socialAccounts,
    ) {}

    /**
     * Resolve (or create) a local user from a Socialite profile and return an API token.
     *
     * @return array{user: User, token: string}
     */
    public function execute(SocialProvider $provider, SocialiteUser $oauthUser): array
    {
        $account = $this->socialAccounts->findByProvider($provider->value, $oauthUser->getId());

        if ($account) {
            $this->socialAccounts->updateTokens($account, [
                'provider_token' => $oauthUser->token ?? null,
                'provider_refresh_token' => $oauthUser->refreshToken ?? null,
                'avatar' => $oauthUser->getAvatar(),
            ]);

            $user = $account->user;
        } else {
            $user = $this->users->findByEmail($oauthUser->getEmail());
            $isNewUser = false;

            if (! $user) {
                $user = $this->users->createUser([
                    'name' => $oauthUser->getName() ?: $oauthUser->getNickname() ?: 'User',
                    'email' => $oauthUser->getEmail(),
                    'password' => Hash::make(Str::random(40)),
                    'email_verified_at' => now(),
                ]);
                $isNewUser = true;
            }

            $this->socialAccounts->createForUser($user->id, [
                'provider' => $provider->value,
                'provider_id' => $oauthUser->getId(),
                'provider_token' => $oauthUser->token ?? null,
                'provider_refresh_token' => $oauthUser->refreshToken ?? null,
                'avatar' => $oauthUser->getAvatar(),
            ]);

            if ($isNewUser) {
                event(new UserRegistered($user));
            }
        }

        $token = $user->createToken($provider->value)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
