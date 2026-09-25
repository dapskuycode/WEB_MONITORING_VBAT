<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\PaymentTransaction;
use App\Models\WebhookLog;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    /**
     * POST /api/webhooks/midtrans — Midtrans payment notification
     */
    public function midtrans(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Log webhook immediately
        $webhookLog = WebhookLog::create([
            'webhook_type' => 'midtrans',
            'external_id' => $payload['order_id'] ?? null,
            'status' => 'received',
            'payload' => $payload,
        ]);

        try {
            // Verify signature
            $orderId = $payload['order_id'] ?? '';
            $statusCode = $payload['status_code'] ?? '';
            $grossAmount = $payload['gross_amount'] ?? '';
            $signature = $payload['signature_key'] ?? '';

            $signatureValid = $this->midtrans->verifySignature($orderId, $statusCode, $grossAmount, $signature);

            $webhookLog->update([
                'signature_data' => compact('orderId', 'statusCode', 'grossAmount'),
                'signature_valid' => $signatureValid,
            ]);

            if (!$signatureValid) {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => 'Invalid signature',
                ]);
                Log::warning('Midtrans webhook signature invalid', ['order_id' => $orderId]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Find transaction
            $transaction = PaymentTransaction::where('external_reference', $orderId)->first();

            if (!$transaction) {
                $webhookLog->update([
                    'status' => 'failed',
                    'error_message' => 'Transaction not found',
                ]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Idempotency check
            if (in_array($transaction->status, ['settlement', 'refund'])) {
                $webhookLog->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
                Log::info('Midtrans webhook replay ignored (already processed)', ['order_id' => $orderId]);
                return response()->json(['message' => 'Already processed'], 200);
            }

            $transactionStatus = $payload['transaction_status'] ?? '';
            $fraudStatus = $payload['fraud_status'] ?? 'accept';

            // Process transaction status
            DB::transaction(function () use ($transaction, $transactionStatus, $fraudStatus, $payload, $webhookLog) {
                $oldStatus = $transaction->status;

                // Map Midtrans status → internal status
                match ($transactionStatus) {
                    'capture' => $fraudStatus === 'accept' ? $this->handleSettlement($transaction, $payload) : null,
                    'settlement' => $this->handleSettlement($transaction, $payload),
                    'pending' => $transaction->update(['status' => 'pending']),
                    'deny', 'cancel' => $transaction->update(['status' => 'cancelled', 'cancelled_at' => now()]),
                    'expire' => $transaction->update(['status' => 'expired']),
                    'refund' => $this->handleRefund($transaction),
                    default => null,
                };

                $transaction->update(['midtrans_response' => $payload]);

                $webhookLog->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);

                Log::info('Midtrans webhook processed', [
                    'order_id' => $transaction->external_reference,
                    'old_status' => $oldStatus,
                    'new_status' => $transaction->fresh()->status,
                ]);
            });

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            $webhookLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $webhookLog->retry_count + 1,
            ]);

            Log::error('Midtrans webhook processing failed', [
                'error' => $e->getMessage(),
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Handle settlement → grant entitlement
     */
    private function handleSettlement(PaymentTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'settlement',
            'settled_at' => now(),
            'payment_method' => $payload['payment_type'] ?? null,
        ]);

        // Grant entitlement based on package
        $package = $transaction->package;
        $entitlements = $package->entitlements;

        $courseTypes = is_array($entitlements['course_type'] ?? null)
            ? $entitlements['course_type']
            : [$entitlements['course_type'] ?? null];

        foreach ($courseTypes as $courseType) {
            if (!$courseType) continue;

            // Check existing entitlement (avoid duplicate)
            $existing = Entitlement::where('user_id', $transaction->user_id)
                ->where('source_type', 'payment_package')
                ->where('source_id', $package->id)
                ->whereJsonContains('metadata->course_type', $courseType)
                ->first();

            if ($existing) {
                Log::info('Entitlement already exists', [
                    'user_id' => $transaction->user_id,
                    'course_type' => $courseType,
                ]);
                continue;
            }

            Entitlement::create([
                'user_id' => $transaction->user_id,
                'source_type' => 'payment_package',
                'source_id' => $package->id,
                'expires_at' => $package->type === 'subscription' && $package->subscription_months
                    ? now()->addMonths($package->subscription_months)
                    : null, // one_time = permanent
                'is_active' => true,
                'metadata' => ['course_type' => $courseType, 'transaction_id' => $transaction->id],
            ]);
        }
    }

    /**
     * Handle refund → revoke entitlement
     */
    private function handleRefund(PaymentTransaction $transaction): void
    {
        $transaction->update(['status' => 'refund']);

        // Revoke entitlements created by this transaction
        Entitlement::where('user_id', $transaction->user_id)
            ->where('source_type', 'payment_package')
            ->where('source_id', $transaction->payment_package_id)
            ->whereJsonContains('metadata->transaction_id', $transaction->id)
            ->update(['is_active' => false]);

        Log::info('Entitlements revoked due to refund', ['transaction_id' => $transaction->id]);
    }
}
