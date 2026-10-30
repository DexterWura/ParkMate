<?php

namespace App\Domains\User\Controllers;

use App\Domains\User\Actions\LoginUserAction;
use App\Domains\User\Actions\RegisterUserAction;
use App\Domains\User\Actions\ResetPasswordAction;
use App\Domains\User\Actions\SendPasswordResetLinkAction;
use App\Domains\User\DTOs\LoginUserDTO;
use App\Domains\User\DTOs\RegisterUserDTO;
use App\Domains\User\Requests\ForgotPasswordRequest;
use App\Domains\User\Requests\LoginRequest;
use App\Domains\User\Requests\RegisterRequest;
use App\Domains\User\Requests\ResetPasswordRequest;
use App\Domains\User\Resources\UserResource;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        $user = $action->execute(RegisterUserDTO::fromArray($request->validated()));

        return $this->created([
            'user' => new UserResource($user),
        ], 'Registration successful. Please verify your email.');
    }

    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $result = $action->execute(LoginUserDTO::fromArray($request->validated()));

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(message: 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function forgotPassword(
        ForgotPasswordRequest $request,
        SendPasswordResetLinkAction $action
    ): JsonResponse {
        $action->execute($request->validated('email'));

        return $this->success(message: 'Password reset link sent.');
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordAction $action
    ): JsonResponse {
        $action->execute($request->validated());

        return $this->success(message: 'Password has been reset.');
    }
}
