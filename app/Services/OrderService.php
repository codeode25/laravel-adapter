<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;
use App\Services\Payment\PaymentGatewayFactory;

class OrderService
{

    public function __construct(private readonly PaymentGatewayFactory $factory) {}

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

        $adapter = $this->factory->make($gateway);
        return $adapter->pay($order);

        // side ffects
    }
}