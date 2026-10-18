<?php

namespace App\Http\Controllers;

use App\Services\TariffSlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SignupLinkController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $result = TariffSlugService::resolve($slug);

        // Unknown, inactive, deleted, or owned by an inactive reseller: all look identical.
        abort_if($result === null, 410, 'This signup link is no longer active.');

        // Old slug: send the visitor to the current canonical link.
        // 302 (not 301): browsers cache 301s forever, which loops if a slug is ever reverted.
        if ($result['is_alias']) {
            return redirect()->route('register.slug', ['slug' => $result['canonical_slug']]);
        }

        // Only the slug is stored. The Register page re-resolves it on the server every time;
        // no plan id ever goes through the URL, a form field, or the browser.
        $request->session()->put(TariffSlugService::SESSION_KEY, $result['canonical_slug']);

        return redirect('/register');
    }
}
