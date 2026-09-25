<?php

namespace App\Services;

use App\Models\PaymentPackage;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MidtransService
{
    private string $serverKey;
    private string $clientKey;
    private string $snapUrl;
    private bool $isProduction;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key', '');
        $this->clientKey = config('services.midtrans.client_key', '');
        $this->isProduction = config('services.midtrans.is_production', false);
        $this->snapUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * Create Midtrans Snap transaction
     */
    public function createTransaction(User $user, PaymentPackage $package): array
    {
        $orderId = 'VBAT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));

        // Create pending transaction record
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'payment_package_id' => $package->id,
            'external_reference' => $orderId,
            'amount' => $package->price,
            'status' => 'pending',
        ]);

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $package->price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
            ],
            'item_details' => [[
                'id' => $package->code,
                'price' => (int) $package->price,
                'quantity' => 1,
                'name' => $package->name,
            ]],
            'callbacks' => [
                'finish' => config('app.url') . '/payment/finish',
            ],
        ];

        $response = Http::withBasicAuth($this->serverKey, '')
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->post($this->snapUrl, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Midtrans API error: ' . $response->body());
        }

        $data = $response->json();
        $transaction->update(['midtrans_response' => $data]);

        return [
            'transaction_id' => $transaction->id,
            'order_id' => $orderId,
            'snap_token' => $data['token'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signature): bool
    {
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return hash_equals($hash, $signature);
    }

    /**
     * Get transaction status from Midtrans
     */
    public function getTransactionStatus(string $orderId): array
    {
        $url = $this->isProduction
            ? "https://api.midtrans.com/v2/{$orderId}/status"
            : "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

        $response = Http::withBasicAuth($this->serverKey, '')
            ->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch transaction status: ' . $response->body());
        }

        return $response->json();
    }
}
