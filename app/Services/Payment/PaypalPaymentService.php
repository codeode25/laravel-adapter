<?php

namespace App\Services\Payment;

use Exception;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;
use App\Services\Payment\PaymentGatewayInterface;

class PaypalPaymentService implements PaymentGatewayInterface
{
    public function __construct(private readonly Paypal $paypal) {}

    public function pay(Order $order): string
    {
        $token = $this->paypal->getAccessToken();
        $this->paypal->setAccessToken($token);

        $amount = number_format($order->total / 100, 2, '.', '');
        
        try {
            $response = $this->paypal->createOrder([
                'intent'=> 'CAPTURE',
                'purchase_units'=> [[
                    'reference_id' => $order->id,
                    'amount'=> [
                        'currency_code'=> $order->currency,
                        'value'=> $amount,
                    ]
                ]],
                'application_context' => [
                    'cancel_url' => "http://localhost:5173/checkout/cancel/{$order->id}",
                    'return_url' => "http://localhost:5173/checkout/success/{$order->id}",
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