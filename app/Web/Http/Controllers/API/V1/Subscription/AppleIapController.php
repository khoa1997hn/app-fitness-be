<?php

namespace App\Web\Http\Controllers\API\V1\Subscription;

use App\Share\Exceptions\Subscription\InvalidReceiptException;
use App\Share\Http\Controllers\Controller;
use App\Share\Services\Subscription\AppleService;
use App\Share\Utils\ResponseAPI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppleIapController extends Controller
{
    public function __construct(
        protected AppleService $appleService
    ) {}

    /**
     * Verify Apple App Store purchase
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipt' => 'required|string',
        ]);

        $user = auth()->user();

        try {
            $subscription = $this->appleService->verifyReceipt(
                $validated['receipt'],
                $user
            );

            return ResponseAPI::success([
                'subscription' => $subscription,
            ], __('messages.receipt_verified_successfully'));
        } catch (InvalidReceiptException $e) {
            return ResponseAPI::error(__('messages.invalid_receipt', ['detail' => $e->getMessage()]), 400);
        }
    }
}
