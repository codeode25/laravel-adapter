<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;

class OrderService
{
    public function createOrder(User $user, array $productIds, string $gateway): string
    {
        if (!in_array($gateway, ['paypal', 'stripe'])) {
            throw new \Exception('Invalid payment gateway');
        }

        $products = Product::whereIn('id', $productIds)->get();

        if ($products->count() !== count(array_unique($productIds))) {
            throw new Exception('One or more products not found.');
        }

        $total = $products->sum('price');

        $order = Order::create([
            'user_id' => $user->id,
            'total' => $total,
            'currency' => 'USD',
            'payment_gateway' => $gateway,
        ]);

        $order->products()->attach(
            collect($productIds)->mapWithKeys(fn($id) => [$id => ['quantity' => 1]])
        );

        // stripe
        if ($gateway === 'stripe') {
            return $this->createStripeCheckoutSession($order);
        }

        // paypal
        if ($gateway === 'paypal') {
            return $this->createPayPalApprovalLink($order);
        }

        // side ffects
    }

    private function createStripeCheckoutSession(Order $order): string
    {
        $stripe = new StripeClient(config('stripe.stripe_secret'));
        try {
            $session = $stripe->checkout->sessions->create([
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

    private function createPayPalApprovalLink(Order $order): string
    {
        $paypal = new Paypal(config('paypal'));
        $token = $paypal->getAccessToken();
        $paypal->setAccessToken($token);

        $amount = number_format($order->total / 100, 2, '.', '');
        
        try {
            $response = $paypal->createOrder([
                'intent'=> 'CAPTURE',
                'purchase_units'=> [[
                    'reference_id' => $order->id,
                    'amount'=> [
                        'currency_code'=> $order->currency,
                        'value'=> $amount,
                    ]
                ]],
                'application_context' => [
                    'cancel_url' => "http://localhost:5173/checkout/success/{$order->id}",
                    'return_url' => "http://localhost:5173/checkout/cancel/{$order->id}",
                ],
            ]);

            if (isset($response['error'])) {
                Log::error('PayPal createOrder error: ' . json_encode($response['error']));
                throw new \Exception($response['error']['message'] ?? 'PayPal error');
            }

            if (!isset($response['links'][1]['href'])) {
                throw new Exception('No approval URL returned from PayPal.');
            }

            $order->update(['transaction_id' => $response['id']]);

            return $response['links'][1]['href'];
        } catch (Exception $e) {
            Log::error('PayPal order creation failed: ' . $e->getMessage());
            throw new Exception('Unable to create PayPal order. Please try again.');
        }
    }
}