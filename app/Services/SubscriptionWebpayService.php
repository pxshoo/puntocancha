<?php

namespace App\Services;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubscriptionWebpayService
{
    private function client(string $mode): PendingRequest
    {
        if ($mode === 'production' && (! config('billing.commerce_code') || ! config('billing.api_key')
            || ! config('billing.legal_approved') || ! str_starts_with(config('app.url'), 'https://'))) {
            throw ValidationException::withMessages(['payment' => 'Los pagos reales de la plataforma aún no están habilitados.']);
        }
        if (! in_array($mode, ['integration', 'production'], true)) {
            throw ValidationException::withMessages(['payment' => 'El pago de suscripciones no está disponible por ahora.']);
        }

        return Http::baseUrl($this->host($mode).'/rswebpaytransaction/api/webpay/v1.2/transactions')
            ->withHeaders([
                'Tbk-Api-Key-Id' => $mode === 'production' ? config('billing.commerce_code') : config('webpay.integration_code'),
                'Tbk-Api-Key-Secret' => $mode === 'production' ? config('billing.api_key') : config('webpay.integration_key'),
            ])->asJson()->acceptJson()->connectTimeout(5)->timeout(20);
    }

    private function host(string $mode): string
    {
        return $mode === 'production' ? 'https://webpay3g.transbank.cl' : 'https://webpay3gint.transbank.cl';
    }

    public function create(SubscriptionPayment $payment): array
    {
        $data = $this->client($payment->mode)->post('', [
            'buy_order' => $payment->buy_order, 'session_id' => $payment->session_id, 'amount' => $payment->amount,
            'return_url' => route('subscription.return'),
        ])->throw()->json();
        if (! is_array($data) || ! is_string($data['token'] ?? null) || ! preg_match('/^[a-zA-Z0-9]{20,128}$/', $data['token'])
            || ! is_string($data['url'] ?? null) || ! str_starts_with($data['url'], $this->host($payment->mode).'/')) {
            throw new RuntimeException('Respuesta de pago inválida.');
        }

        return $data;
    }

    public function result(SubscriptionPayment $payment, bool $commit): array
    {
        $path = '/'.rawurlencode($payment->token);
        $response = $commit ? $this->client($payment->mode)->put($path) : $this->client($payment->mode)->get($path);
        if ($commit && ! $response->successful()) {
            $response = $this->client($payment->mode)->get($path);
        }

        return $response->throw()->json();
    }
}
