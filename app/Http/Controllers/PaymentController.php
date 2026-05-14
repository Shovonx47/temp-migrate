<?php

namespace App\Http\Controllers;

use App\Actions\Payments\HandlePaymentCallback;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __invoke(Request $request, string $gateway)
    {
        $ppId = $request->query('pp_id');

        if (! $ppId) {
            return redirect()->to('/')->with('error', 'Missing payment reference.');
        }

        return app(HandlePaymentCallback::class)->handle($gateway, $ppId);
    }
}
