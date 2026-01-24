<?php

namespace App\Web\Http\Controllers\API\V1\Subscription;

use App\Share\Exceptions\Subscription\InvalidReceiptException;
use App\Share\Http\Controllers\Controller;
use App\Share\Services\Subscription\GoogleService;
use App\Share\Utils\ResponseAPI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleIapController extends Controller
{
    public function __construct(
        protected GoogleService $googleService
    ) {}

    /**
     * Verify Google Play purchase
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_token' => 'required|string',
            'item_id' => 'required|string',
        ]);

        $user = auth()->user();

        try {
            $subscription = $this->googleService->verifyPurchase(
                $validated['purchase_token'],
                $validated['item_id'],
                $user
            );

            return ResponseAPI::success([
                'subscription' => $subscription,
            ], __('messages.purchase_verified_successfully'));
        } catch (InvalidReceiptException $e) {
            return ResponseAPI::error(__('messages.invalid_receipt', ['detail' => $e->getMessage()]), 400);
        }
    }
}
