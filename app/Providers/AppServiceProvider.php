<?php

namespace App\Providers;

use Stripe\StripeClient;
use Srmklive\PayPal\Services\PayPal;
use Illuminate\Support\ServiceProvider;
use App\Services\Payment\PaypalPaymentService;
use App\Services\Payment\StripePaymentService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('stripe.stripe_secret'));
        });

        $this->app->singleton(Paypal::class, function () {
            return new Paypal(config('paypal'));
        });

        $this->app->bind('stripe.adapter', function ($app) {
            return new StripePaymentService($app->make(StripeClient::class));
        });

        $this->app->bind('paypal.adapter', function ($app) {
            return new PaypalPaymentService($app->make(Paypal::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
