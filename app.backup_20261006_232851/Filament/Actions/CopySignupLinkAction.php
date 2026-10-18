<?php

namespace App\Filament\Actions;

use App\Models\TariffPlan;
use App\Services\TariffSlugService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

class CopySignupLinkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'copySignupLink';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Copy Signup Link')
            ->icon(Heroicon::OutlinedLink)
            ->color('info')
            ->disabled(fn (TariffPlan $record): bool => ! $record->status?->isActive() || blank($record->slug))
            ->tooltip(function (TariffPlan $record): ?string {
                if (! $record->status?->isActive()) {
                    return 'This plan is not Active, so its signup link will not work.';
                }

                if (blank($record->slug)) {
                    return 'This plan does not have a signup slug.';
                }

                return null;
            })
            ->alpineClickHandler(function (TariffPlan $record): string {
                $urlJs = Js::from(TariffSlugService::signupUrl($record));

                return <<<JS
                    (function () {
                        const url = {$urlJs};
                        const notify = (title, body = null, isSuccess = true) => {
                            if (typeof FilamentNotification !== 'undefined') {
                                const notif = new FilamentNotification().title(title);
                                if (body) {
                                    notif.body(body);
                                }
                                isSuccess ? notif.success() : notif.danger();
                                notif.send();
                            }
                        };
                        const fallback = (text) => {
                            const ta = document.createElement('textarea');
                            ta.value = text;
                            ta.setAttribute('readonly', '');
                            ta.style.position = 'fixed';
                            ta.style.left = '-9999px';
                            ta.style.opacity = '0';
                            document.body.appendChild(ta);
                            ta.focus();
                            ta.select();
                            ta.setSelectionRange(0, 99999);
                            let ok = false;
                            try {
                                ok = document.execCommand('copy');
                            } catch (e) {}
                            document.body.removeChild(ta);
                            if (ok) {
                                notify('Signup link copied to clipboard.', text, true);
                            } else {
                                window.prompt('Copy signup link manually:', text);
                            }
                        };

                        if (window.isSecureContext && window.navigator?.clipboard?.writeText) {
                            window.navigator.clipboard.writeText(url)
                                .then(() => notify('Signup link copied to clipboard.', url, true))
                                .catch(() => fallback(url));
                        } else {
                            fallback(url);
                        }
                    })()
                JS;
            });
    }
}
