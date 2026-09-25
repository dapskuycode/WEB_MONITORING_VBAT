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
        $validated = $request->validate([
            'package_id' => 'required|exists:payment_packages,id',
        ]);

        $package = PaymentPackage::findOrFail($validated['package_id']);

        if (!$package->is_active) {
            return response()->json(['error' => 'Package not available'], 400);
        }

        $user = Auth::user();

        try {
            $result = $this->midtrans->createTransaction($user, $package);
            return response()->json(['data' => $result], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create transaction', 'message' => $e->getMessage()], 500);
        }
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
