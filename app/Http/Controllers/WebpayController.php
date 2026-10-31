<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartWebpayRequest;
use App\Models\BookingPayment;
use App\Services\BookingCheckoutService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class WebpayController extends Controller
{
    public function store(StartWebpayRequest $request, BookingCheckoutService $checkout): Response|RedirectResponse
    {
        try {
            $payment = $checkout->start($request->validated());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable) {
            return back()->withInput($request->except(['_token']))->withErrors(['payment' => 'No fue posible conectar con Webpay. No se confirmó la reserva. Intenta nuevamente.']);
        }
        if ($payment->status !== 'initialized' || $payment->expires_at->isPast()) {
            return redirect($this->receiptUrl($payment));
        }

        return response()->view('player.webpay-redirect', compact('payment'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request, BookingCheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'token_ws' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9]{20,128}$/'],
            'TBK_TOKEN' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9]{20,128}$/'],
        ]);
        $token = $data['token_ws'] ?? $data['TBK_TOKEN'] ?? null;
        if (! $token) {
            return to_route('player.explore')->withErrors(['payment' => 'El pago terminó sin un token válido. Si hubo un cargo, contacta al club antes de intentar nuevamente.']);
        }
        $payment = BookingPayment::where('token_hash', hash('sha256', $token))->firstOrFail();
        try {
            $payment = $checkout->settle($payment, isset($data['token_ws']));
        } catch (Throwable) {
            return redirect($this->receiptUrl($payment))->withErrors(['payment' => 'Estamos verificando el pago. No pagues nuevamente; puedes consultar su estado aquí.']);
        }

        return redirect($this->receiptUrl($payment));
    }

    public function receipt(BookingPayment $payment): Response
    {
        $payment->load(['booking.court' => fn ($query) => $query->withoutGlobalScope('club'), 'club']);

        return response()->view('player.webpay-result', compact('payment'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function refresh(Request $request, BookingPayment $payment, BookingCheckoutService $checkout): RedirectResponse
    {
        try {
            $checkout->settle($payment, false);
        } catch (Throwable) {
            return redirect($this->receiptUrl($payment))->withErrors(['payment' => 'Webpay todavía no responde. Mantén este comprobante y consulta nuevamente.']);
        }

        return redirect($this->receiptUrl($payment));
    }

    private function receiptUrl(BookingPayment $payment): string
    {
        return URL::signedRoute('webpay.receipt', ['payment' => $payment->id]);
    }
}
