<?php

namespace App\Services;

use App\Models\BookingPayment;
use App\Models\Club;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WebpayService
{
    public function ready(Club $club): bool
    {
        return ($club->webpay_mode === 'payment_link' && filled($club->booking_payment_link))
            || $club->webpay_mode === 'integration'
            || ($club->webpay_mode === 'production' && config('webpay.production_enabled') && $club->webpay_commerce_code && $club->webpay_api_key);
    }

    private function client(Club $club, string $mode): PendingRequest
    {
        if ($mode === 'production') {
            if (! config('webpay.production_enabled') || ! $club->webpay_commerce_code || ! $club->webpay_api_key) {
                throw ValidationException::withMessages(['payment' => 'El club todavía no tiene habilitados los cobros reales.']);
            }
            $code = $club->webpay_commerce_code;
            $key = $club->webpay_api_key;
        } else {
            $code = config('webpay.integration_code');
            $key = config('webpay.integration_key');
        }

        return Http::baseUrl($this->host($mode).'/rswebpaytransaction/api/webpay/v1.2/transactions')
            ->withHeaders(['Tbk-Api-Key-Id' => $code, 'Tbk-Api-Key-Secret' => $key])->acceptJson()->asJson()->connectTimeout(5)->timeout(20);
    }

    private function host(string $mode): string
    {
        return $mode === 'production' ? 'https://webpay3g.transbank.cl' : 'https://webpay3gint.transbank.cl';
    }

    public function create(BookingPayment $payment): array
    {
        $data = $this->client($payment->club, $payment->mode)->post('', [
            'buy_order' => $payment->buy_order, 'session_id' => $payment->session_id, 'amount' => $payment->amount,
            'return_url' => route('webpay.return'),
        ])->throw()->json();
        if (! is_array($data) || ! is_string($data['token'] ?? null) || ! preg_match('/^[a-zA-Z0-9]{20,128}$/', $data['token'])
            || ! is_string($data['url'] ?? null) || ! str_starts_with($data['url'], $this->host($payment->mode).'/')) {
            throw new RuntimeException('Respuesta inválida de Webpay.');
        }

        return $data;
    }

    public function result(BookingPayment $payment, bool $commit = true): array
    {
        $client = $this->client($payment->club, $payment->mode);
        $path = '/'.rawurlencode($payment->token);
        $response = $commit ? $client->put($path) : $client->get($path);
        if ($commit && ! $response->successful()) {
            $response = $this->client($payment->club, $payment->mode)->get($path);
        }
        $data = $response->throw()->json();
        if (! is_array($data)) {
            throw new RuntimeException('Respuesta inválida de Webpay.');
        }

        return $data;
    }
}
