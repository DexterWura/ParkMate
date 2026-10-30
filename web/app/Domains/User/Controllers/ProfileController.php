<?php

namespace App\Domains\User\Controllers;

use App\Domains\User\Requests\ChangePasswordRequest;
use App\Domains\User\Requests\UpdateProfileRequest;
use App\Domains\User\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseController
{
    public function __construct(protected UserService $users) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->users->updateProfile($request->user(), $request->validated());

        return $this->success(new UserResource($user), 'Profile updated.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->users->changePassword($request->user(), $request->validated('password'));
        $request->user()->tokens()->delete();

        return $this->success(message: 'Password changed. Please log in again.');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->error('Email already verified.', 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->success(message: 'Verification email sent.');
    }
}
