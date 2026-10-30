<?php

namespace App\Domains\Install\Http\Middleware;

use App\Domains\Install\Services\ApplicationKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationKey
{
    public function __construct(
        private readonly ApplicationKeyService $keys
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->keys->ensureRuntimeKey();

        return $next($request);
    }
}
