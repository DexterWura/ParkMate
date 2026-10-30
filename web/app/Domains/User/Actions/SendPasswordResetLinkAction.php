<?php

namespace App\Domains\User\Actions;

use App\Core\Exceptions\DomainException;
use App\Shared\Actions\BaseAction;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction extends BaseAction
{
    public function execute(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new DomainException(__($status), 422);
        }
    }
}
