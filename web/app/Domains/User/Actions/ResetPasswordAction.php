<?php

namespace App\Domains\User\Actions;

use App\Core\Exceptions\DomainException;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Password;

class ResetPasswordAction extends BaseAction
{
    public function execute(array $credentials): void
    {
        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new DomainException(__($status), 422);
        }
    }
}
