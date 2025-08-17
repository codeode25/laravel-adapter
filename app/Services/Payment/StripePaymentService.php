<?php

namespace App\Services\Payment;

use Exception;
use App\Models\Order;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\PaymentGatewayInterface;

class StripePaymentService implements PaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function pay(Order $order): string
    {
        try {
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $order->currency,
                        'product_data' => ['name' => "Order #{$order->id}"],
                        'unit_amount' => $order->total,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => "http://localhost:5173/checkout/success/{$order->id}?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => "http://localhost:5173/checkout/cancel/{$order->id}?session_id={CHECKOUT_SESSION_ID}",
            ]);

            $order->update(['transaction_id' => $session->id]);
            
            return $session->url;
        } catch (Exception $e) {
            Log::error('Stripe session creation failed: ' . $e->getMessage());
            throw new Exception('Unable to create Stripe session. Please try again.');
        }
    }
}