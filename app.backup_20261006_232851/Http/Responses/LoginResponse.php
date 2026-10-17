<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $panelUrl = $panel?->getUrl() ?? url('/');

        $intended = session()->get('url.intended');

        if ($intended) {
            $intendedPath = (string) (parse_url((string) $intended, PHP_URL_PATH) ?? '');
            $panelPath = trim((string) ($panel?->getPath() ?? ''), '/');

            // If panel has a non-empty path prefix (like 'superman' or 'sp')
            if ($panelPath !== '') {
                $trimmedIntended = trim($intendedPath, '/');
                if (
                    $trimmedIntended === $panelPath ||
                    str_starts_with($trimmedIntended, $panelPath.'/')
                ) {
                    return redirect()->intended($panelUrl);
                }
            } else {
                // Root panel ('customer'): ensure intended URL does NOT belong to admin or sp panels
                $trimmedIntended = trim($intendedPath, '/');
                if (
                    ! str_starts_with($trimmedIntended, 'superman') &&
                    ! str_starts_with($trimmedIntended, 'sp') &&
                    ! str_starts_with($trimmedIntended, 'login') &&
                    ! str_starts_with($trimmedIntended, 'signin')
                ) {
                    return redirect()->intended($panelUrl);
                }
            }

            // Cross-panel or invalid intended URL — discard it from session
            session()->forget('url.intended');
        }

        return redirect()->to($panelUrl);
    }
}
