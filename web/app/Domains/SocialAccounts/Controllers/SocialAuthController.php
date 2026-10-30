<?php

namespace App\Domains\SocialAccounts\Controllers;

use App\Core\Exceptions\DomainException;
use App\Domains\SocialAccounts\Actions\HandleSocialLoginAction;
use App\Domains\SocialAccounts\Enums\SocialProvider;
use App\Domains\User\Resources\UserResource;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends BaseController
{
    public function redirect(string $provider): JsonResponse|RedirectResponse
    {
        $social = $this->resolveProvider($provider);

        $url = Socialite::driver($social->value)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        if (request()->wantsJson()) {
            return $this->success(['redirect_url' => $url]);
        }

        return redirect()->away($url);
    }

    public function callback(string $provider, HandleSocialLoginAction $action): JsonResponse
    {
        $social = $this->resolveProvider($provider);

        try {
            $oauthUser = Socialite::driver($social->value)->stateless()->user();
        } catch (Throwable $e) {
            throw new DomainException('Unable to authenticate with '.$social->value.'.', 401, $e instanceof \Exception ? $e : null);
        }

        $result = $action->execute($social, $oauthUser);

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Authenticated successfully.');
    }

    protected function resolveProvider(string $provider): SocialProvider
    {
        return SocialProvider::tryFrom($provider)
            ?? throw new DomainException("Unsupported social provider: {$provider}.", 404);
    }
}
