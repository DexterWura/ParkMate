<?php

namespace App\Shared\Controllers;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

/**
 * Base API controller. All domain controllers should extend this
 * for consistent authorization and validation behavior.
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    protected function success(mixed $data = null, string $message = 'Success', int $status = 200)
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created')
    {
        return ApiResponse::created($data, $message);
    }

    protected function error(string $message = 'Error', int $status = 400, mixed $errors = null)
    {
        return ApiResponse::error($message, $status, $errors);
    }
}
