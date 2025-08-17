<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use function Pest\Laravel\{actingAs, putJson};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->group('feature', 'api');


it('creates an order and returns transaction id for stripe', function () {
    $this->actingAs(User::factory()->create());

    $products = Product::factory()->count(2)->create();

    $response = $this->postJson('/api/checkout', [
        'product_ids' => $products->pluck('id')->toArray(),
        'gateway' => 'stripe',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'redirect_url',
        ]);
});

it('creates an order and returns transaction id for paypal', function () {
    $this->actingAs(User::factory()->create());

    $products = Product::factory()->count(2)->create();

    $response = $this->postJson('/api/checkout', [
        'product_ids' => $products->pluck('id')->toArray(),
        'gateway' => 'paypal',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'redirect_url',
        ]);
});
