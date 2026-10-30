<?php

namespace App\Domains\Install\Http\Middleware;

use App\Domains\Install\Services\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsInstalled
{
    public function __construct(
        private readonly InstallationState $installation
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (
            $this->installation->isInstalled()
            || $request->is('install', 'install/*', 'up')
        ) {
            return $next($request);
        }

        if ($request->is('api', 'api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'The application has not been installed.',
            ], 503);
        }

        return redirect()->route('install.index');
    }
}
