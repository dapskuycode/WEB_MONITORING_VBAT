<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentPackage;
use App\Models\PaymentTransaction;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentApiController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    /**
     * GET /api/payment/packages — Public catalog
     */
    public function packages(): JsonResponse
    {
        $packages = PaymentPackage::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'description', 'price', 'type', 'subscription_months']);

        return response()->json(['data' => $packages]);
    }

    /**
     * POST /api/payment/transactions — Create transaction (auth required)
     */
    public function createTransaction(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => 'PURCHASES_UNAVAILABLE'],
            'message' => 'Pembelian menunggu integrasi pembayaran dan entitlement yang terverifikasi.',
        ], 503);
    }

    /**
     * GET /api/payment/transactions — User transaction history (auth required)
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = PaymentTransaction::with('package:id,code,name')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * GET /api/payment/transactions/{id} — Get single transaction detail
     */
    public function transactionDetail(int $id): JsonResponse
    {
        $transaction = PaymentTransaction::with('package')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json(['data' => $transaction]);
    }
}
