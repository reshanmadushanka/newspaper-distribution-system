<?php

namespace Tests\Feature\Invoices;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Invoices\Enums\InvoiceStatus;
use App\Domain\Shops\Models\Shop;
use Database\Factories\ShopFactory;
use App\Domain\Newspapers\Models\Newspaper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Shop $shop;
    private Newspaper $newspaper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->shop = ShopFactory::new()->create(['status' => 'active']);
        $this->newspaper = Newspaper::factory()->create(['status' => 'active', 'price' => 10.00]);
        
        // Create price record for forecasting
        \Illuminate\Support\Facades\DB::table('newspaper_prices')->insert([
            'newspaper_id' => $this->newspaper->id,
            'price' => 10.00,
            'effective_from' => now()->subMonth()->toDateString(),
            'created_by' => $this->user->id,
            'created_at' => now(),
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_can_generate_forecast_for_tomorrow(): void
    {
        $response = $this->postJson('/dispatch/forecast', [
            'dispatch_date' => now()->addDay()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['forecasts']);
    }

    public function test_can_generate_invoices_from_forecast(): void
    {
        $dispatchDate = now()->addDay()->toDateString();

        // Create forecast record with non-zero quantity
        \Illuminate\Support\Facades\DB::table('dispatch_forecasts')->insert([
            'shop_id' => $this->shop->id,
            'newspaper_id' => $this->newspaper->id,
            'forecast_date' => $dispatchDate,
            'suggested_quantity' => 5,
            'final_quantity' => 5,
            'method' => 'same_weekday',
            'source_data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Generate invoices from forecasts
        $response = $this->postJson('/dispatch/generate', [
            'dispatch_date' => $dispatchDate,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['invoices', 'message']);

        $this->assertDatabaseHas('invoices', [
            'shop_id' => $this->shop->id,
            'dispatch_date' => $dispatchDate,
            'status' => InvoiceStatus::DRAFT->value,
        ]);
    }

    public function test_generated_invoice_has_correct_snapshot_prices(): void
    {
        $dispatchDate = now()->addDay()->toDateString();

        // Create forecast record with non-zero quantity
        \Illuminate\Support\Facades\DB::table('dispatch_forecasts')->insert([
            'shop_id' => $this->shop->id,
            'newspaper_id' => $this->newspaper->id,
            'forecast_date' => $dispatchDate,
            'suggested_quantity' => 5,
            'final_quantity' => 5,
            'method' => 'same_weekday',
            'source_data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/dispatch/generate', [
            'dispatch_date' => $dispatchDate,
        ]);

        $invoice = \App\Domain\Invoices\Models\Invoice::where('shop_id', $this->shop->id)->first();
        $item = $invoice->items->first();

        $this->assertNotNull($item);
        $this->assertEquals($this->newspaper->price, $item->unit_price);
        $this->assertEquals($this->newspaper->name, $item->newspaper_name);
    }

    public function test_can_update_draft_invoice(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $item = \Database\Factories\InvoiceItemFactory::new()->create([
            'invoice_id' => $invoice->id,
            'newspaper_id' => $this->newspaper->id,
            'quantity' => 10,
            'unit_price' => 10.00,
            'line_total' => 100.00,
        ]);

        $response = $this->putJson("/dispatch/{$invoice->id}", [
            'items' => [
                [
                    'id' => $item->id,
                    'quantity' => 15,
                    'reason' => 'Increased demand',
                ],
            ],
        ]);

        $response->assertOk();
        
        $item->refresh();
        $this->assertEquals(15, $item->quantity);
        $this->assertEquals(150.00, $item->line_total);
        $this->assertEquals('Increased demand', $item->manual_adjustment_reason);
    }

    public function test_cannot_update_confirmed_invoice(): void
    {
        $invoice = \Database\Factories\InvoiceFactory::new()->create([
            'shop_id' => $this->shop->id,
            'prepared_by' => $this->user->id,
            'status' => InvoiceStatus::CONFIRMED,
        ]);

        $item = \Database\Factories\InvoiceItemFactory::new()->create([
            'invoice_id' => $invoice->id,
            'newspaper_id' => $this->newspaper->id,
        ]);

        $response = $this->putJson("/dispatch/{$invoice->id}", [
            'items' => [
                ['id' => $item->id, 'quantity' => 20],
            ],
        ]);

        $response->assertStatus(422);
    }
}
