<?php

namespace App\Domains\Install\Controllers;

use App\Domains\Install\Actions\InstallApplicationAction;
use App\Domains\Install\DTOs\InstallDTO;
use App\Domains\Install\Requests\InstallRequest;
use App\Domains\Install\Services\PreflightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class InstallController
{
    public function index(Request $request, PreflightService $preflight): View
    {
        $requestedConnection = $request->old('db_connection', $request->query('db_connection'));
        $selected = in_array($requestedConnection, ['sqlite', 'mysql', 'mariadb'], true)
            ? $requestedConnection
            : 'sqlite';

        return view('install.index', [
            'preflight' => $preflight->inspect($selected),
            'selectedConnection' => $selected,
        ]);
    }

    public function store(InstallRequest $request, InstallApplicationAction $installer): RedirectResponse|View
    {
        try {
            $installer->execute(InstallDTO::fromArray($request->validated()));
        } catch (RuntimeException $exception) {
            return $this->installationFailure($request, $exception->getMessage());
        } catch (Throwable) {
            return $this->installationFailure(
                $request,
                'Installation could not be completed. Check the server logs, then try again.'
            );
        }

        return view('install.complete');
    }

    private function installationFailure(InstallRequest $request, string $message): RedirectResponse
    {
        return redirect()->route('install.index')
            ->withInput($request->safe()->except([
                'db_password',
                'owner_password',
                'owner_password_confirmation',
            ]))
            ->with('install_error', $message);
    }
}
