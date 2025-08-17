<?php

namespace App\Services\Payment;

use Illuminate\Contracts\Container\Container;

class PaymentGatewayFactory
{
    public function __construct(private readonly Container $container) {}

    public function make(string $gateway)
    {
        return match($gateway) {
            'stripe' => $this->container->make('stripe.adapter'),
            'paypal' => $this->container->make('paypal.adapter'),
            default => throw new Exception("Invalid payment gateway"),
        };
    }
}