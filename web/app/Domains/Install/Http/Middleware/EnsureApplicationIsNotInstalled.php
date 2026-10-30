<?php

namespace App\Domains\Install\Http\Middleware;

use App\Domains\Install\Services\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsNotInstalled
{
    public function __construct(
        private readonly InstallationState $installation
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installation->isInstalled()) {
            abort(404);
        }

        return $next($request);
    }
}
