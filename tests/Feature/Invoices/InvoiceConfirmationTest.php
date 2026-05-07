<?php

namespace Tests\Feature\Invoices;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Invoices\Enums\InvoiceStatus;
use App\Domain\Invoices\Services\InvoiceConfirmationService;
use App\Domain\Shops\Models\Shop;
use Database\Factories\ShopFactory;
use App\Domain\Newspapers\Models\Newspaper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Shop $shop;
    private Newspaper $newspaper;
    private InvoiceConfirmationService $confirmationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->shop = ShopFactory::new()->create(['status' => 'active']);
        $this->newspaper = Newspaper::factory()->create(['status' => 'active', 'price' => 10.00]);
        $this->confirmationService = new InvoiceConfirmationService(
            app(\App\Domain\Invoices\Repositories\InvoiceRepositoryInterface::class)
        );

        // Create price record
        \Illuminate\Support\Facades\DB::table('newspaper_prices')->insert([
            'newspaper_id' => $this->newspaper->id,
            'price' => 10.00,
            'effective_from' => now()->subMonth()->toDateString(),
            'created_by' => $this->user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($this->user);
    }

    public function test_can_confirm_draft_invoice(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::DRAFT,
            'dispatch_date' => now()->addDay()->toDateString(),
        ]);

        \Database\Factories\InvoiceItemFactory::new()->create([
            'invoice_id' => $invoice->id,
            'newspaper_id' => $this->newspaper->id,
            'quantity' => 5,
            'unit_price' => 10.00,
            'line_total' => 50.00,
        ]);

        $response = $this->postJson("/dispatch/{$invoice->id}/confirm");

        if ($response->status() === 422) {
            dump($response->json());
        }

        $response->assertOk();
        $response->assertJson(['message' => 'Invoice confirmed successfully']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::CONFIRMED->value,
            'confirmed_by' => $this->user->id,
        ]);
    }

    public function test_cannot_confirm_non_draft_invoice(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::CONFIRMED,
        ]);

        $response = $this->postJson("/dispatch/{$invoice->id}/confirm");

        $response->assertStatus(422);
    }

    public function test_confirmed_invoice_creates_invoice_delivery(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::DRAFT,
            'dispatch_date' => now()->addDay()->toDateString(),
        ]);

        \Database\Factories\InvoiceItemFactory::new()->create([
            'invoice_id' => $invoice->id,
            'newspaper_id' => $this->newspaper->id,
            'quantity' => 5,
            'unit_price' => 10.00,
            'line_total' => 50.00,
        ]);

        $this->postJson("/dispatch/{$invoice->id}/confirm");

        $this->assertDatabaseHas('invoice_deliveries', [
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_mark_delivery_as_dispatched(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::CONFIRMED,
            'dispatch_date' => now()->addDay()->toDateString(),
        ]);

        $delivery = \App\Domain\Invoices\Models\InvoiceDelivery::create([
            'invoice_id' => $invoice->id,
            'channel' => \App\Domain\Invoices\Enums\InvoiceDeliveryChannel::PRINT->value,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/dispatch/deliveries/{$delivery->id}/dispatch");

        $response->assertOk();

        $this->assertDatabaseHas('invoice_deliveries', [
            'id' => $delivery->id,
            'status' => 'sent',
        ]);
    }

    public function test_can_mark_delivery_as_delivered(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::CONFIRMED,
        ]);

        $delivery = \App\Domain\Invoices\Models\InvoiceDelivery::create([
            'invoice_id' => $invoice->id,
            'channel' => \App\Domain\Invoices\Enums\InvoiceDeliveryChannel::PRINT->value,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->postJson("/dispatch/deliveries/{$delivery->id}/deliver");

        $response->assertOk();

        $this->assertDatabaseHas('invoice_deliveries', [
            'id' => $delivery->id,
            'status' => 'delivered',
        ]);
    }
}
