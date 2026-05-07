<?php

namespace Tests\Feature\Invoices;

use App\Domain\Shops\Models\Shop;
use App\Domain\Newspapers\Models\Newspaper;
use App\Models\User;
use Database\Factories\ShopFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastingTest extends TestCase
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

    public function test_can_generate_forecast_for_tomorrow(): void
    {
        $dispatchDate = now()->addDay()->toDateString();

        $response = $this->postJson('/dispatch/forecast', [
            'dispatch_date' => $dispatchDate,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['forecasts']);
        $response->assertJsonCount(1, 'forecasts');
    }

    public function test_forecast_returns_zero_for_no_historical_data(): void
    {
        $dispatchDate = now()->addDay()->toDateString();

        $response = $this->postJson('/dispatch/forecast', [
            'dispatch_date' => $dispatchDate,
        ]);

        $response->assertOk();
        $forecast = $response->json('forecasts.0');
        $this->assertEquals(0, $forecast['suggested_quantity']);
    }

    public function test_forecast_uses_same_weekday_historical_data(): void
    {
        $dispatchDate = now()->addDay()->toDateString();
        $targetWeekday = \Carbon\Carbon::parse($dispatchDate)->dayOfWeek;

        // Create historical invoices for the same weekday
        for ($i = 1; $i <= 3; $i++) {
            $pastDate = \Carbon\Carbon::parse($dispatchDate)->subWeeks($i);
            // Adjust to same weekday
            while ($pastDate->dayOfWeek !== $targetWeekday) {
                $pastDate->subDay();
            }

            $invoice = \Database\Factories\InvoiceFactory::new()->create([
                'shop_id' => $this->shop->id,
                'prepared_by' => $this->user->id,
                'dispatch_date' => $pastDate->toDateString(),
                'status' => 'confirmed',
            ]);

            \Database\Factories\InvoiceItemFactory::new()->create([
                'invoice_id' => $invoice->id,
                'newspaper_id' => $this->newspaper->id,
                'quantity' => 10 * $i,
                'unit_price' => 10.00,
                'line_total' => 100 * $i,
            ]);
        }

        $response = $this->postJson('/dispatch/forecast', [
            'dispatch_date' => $dispatchDate,
        ]);

        $response->assertOk();
        $forecast = $response->json('forecasts.0');
        // Average of 10, 20, 30 = 20
        $this->assertEquals(20, $forecast['suggested_quantity']);
    }

    public function test_can_generate_forecast_for_specific_shop(): void
    {
        $anotherShop = ShopFactory::new()->create(['status' => 'active']);
        $dispatchDate = now()->addDay()->toDateString();

        $response = $this->postJson('/dispatch/forecast', [
            'dispatch_date' => $dispatchDate,
            'shop_id' => $anotherShop->id,
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'forecasts');
        $this->assertEquals($anotherShop->id, $response->json('forecasts.0.shop.id'));
    }

    public function test_forecast_creates_database_record(): void
    {
        $dispatchDate = now()->addDay()->toDateString();

        $this->postJson('/dispatch/forecast', [
            'dispatch_date' => $dispatchDate,
        ]);

        $this->assertDatabaseHas('dispatch_forecasts', [
            'shop_id' => $this->shop->id,
            'newspaper_id' => $this->newspaper->id,
            'forecast_date' => $dispatchDate,
        ]);
    }
}
