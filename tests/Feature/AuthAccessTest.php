<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin/products')->assertRedirect('/login');
    }

    public function test_normal_user_cannot_open_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_admin_can_open_admin_products(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertOk();
    }

    public function test_guest_must_login_before_buying(): void
    {
        $product = Product::create([
            'name' => 'Test product',
            'category' => 'Test',
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->post(route('shop.buy', $product), [
            'customer_name' => 'Guest',
            'quantity' => 1,
        ])->assertRedirect('/login');
    }

    public function test_topup_creates_pending_transaction_then_bank_webhook_credits_wallet(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'wallet_balance' => 970,
        ]);

        $this->actingAs($user)
            ->post(route('wallet.store'), [
                'amount' => 200000,
                'method' => 'bank',
            ])
            ->assertRedirect();

        $this->assertSame(970, $user->fresh()->wallet_balance);

        $transaction = Transaction::whereBelongsTo($user)->firstOrFail();
        $this->assertSame('pending', $transaction->status);

        $this->postJson('/api/bank-webhook', [
            'code' => $transaction->code,
            'amount' => 200000,
            'bank_ref' => 'BANK-001',
        ], ['X-Bank-Webhook-Token' => 'local-bank-webhook-token'])
            ->assertOk()
            ->assertJson(['success' => true, 'credited' => true]);

        $this->assertSame(200970, $user->fresh()->wallet_balance);
    }

    public function test_buying_product_uses_wallet_balance(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'wallet_balance' => 50000,
        ]);
        $product = Product::create([
            'name' => 'Wallet product',
            'category' => 'Test',
            'price' => 13580,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('shop.buy', $product), [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'quantity' => 1,
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertSame(36420, $user->fresh()->wallet_balance);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'total' => 13580,
            'status' => 'paid',
        ]);
    }

    public function test_user_with_low_balance_is_redirected_to_topup(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'wallet_balance' => 1000,
        ]);
        $product = Product::create([
            'name' => 'Expensive product',
            'category' => 'Test',
            'price' => 13580,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('shop.buy', $product), [
                'customer_name' => $user->name,
                'quantity' => 1,
            ])
            ->assertRedirect(route('wallet.topup'));

        $this->assertSame(1000, $user->fresh()->wallet_balance);
    }

    public function test_api_balance_uses_api_key(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'wallet_balance' => 123000,
            'api_key' => 'test-api-key',
        ]);

        $this->getJson('/api/balance', ['X-API-Key' => $user->api_key])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'balance' => 123000,
                'email' => $user->email,
            ]);
    }

    public function test_api_buy_key_is_idempotent_and_uses_wallet(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'wallet_balance' => 50000,
            'api_key' => 'buy-api-key',
        ]);
        $product = Product::create([
            'name' => 'API product',
            'category' => 'API',
            'price' => 20000,
            'stock' => 3,
            'is_active' => true,
        ]);

        $payload = [
            'product_id' => $product->id,
            'idempotency_key' => 'same-request-001',
        ];

        $first = $this->postJson('/api/buy-key', $payload, ['X-API-Key' => $user->api_key])
            ->assertOk()
            ->assertJson(['success' => true]);

        $second = $this->postJson('/api/buy-key', $payload, ['X-API-Key' => $user->api_key])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame($first->json('order_id'), $second->json('order_id'));
        $this->assertSame(30000, $user->fresh()->wallet_balance);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_api_purchase_history_requires_max_seven_days(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'api_key' => 'history-api-key',
        ]);

        $this->getJson('/api/purchase-history?start_date=2026-03-01&end_date=2026-03-20', [
            'X-API-Key' => $user->api_key,
        ])->assertStatus(400);
    }
}
