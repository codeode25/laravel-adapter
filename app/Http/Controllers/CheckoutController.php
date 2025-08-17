<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;

class CheckoutController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function checkout(Request $request)
    {
        $validated = $request->validate(
            [
                'product_ids' => ['required', 'array', 'min:1'],
                'product_ids.*' => 'integer|exists:products,id',
                'gateway' => ['required', 'string', 'in:stripe,paypal'],
            ]
        );

        $user = $request->user();

        try {
            $redirectURL = $this->orderService->createOrder($user, $request->product_ids, $request->gateway);

            return response()->json(['redirect_url' => $redirectURL]);
        } catch (\Exception $e) {
             return response()->json(['error' => 'Error...']);
        }
    }
}
