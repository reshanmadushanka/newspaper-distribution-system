<?php

namespace Tests\Feature\Pricing;

use App\Domain\Newspapers\Enums\Language;
use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Enums\PublicationFrequency;
use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Pricing\Models\NewspaperPrice;
use App\Domain\Pricing\Services\PriceHistoryService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'manage newspapers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->newspaper = Newspaper::create([
            'name' => 'Test Paper',
            'language' => Language::ENGLISH->value,
            'frequency' => PublicationFrequency::DAILY->value,
            'status' => NewspaperStatus::ACTIVE->value,
        ]);

        $this->priceService = app(PriceHistoryService::class);
    }

    public function test_add_price_creates_record(): void
    {
        $price = $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $this->assertDatabaseHas('newspaper_prices', [
            'newspaper_id' => $this->newspaper->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals(50.00, $price->price);
        $this->assertEquals('2026-01-01', $price->effective_from->format('Y-m-d'));
        $this->assertNull($price->effective_to);
        $this->assertTrue($price->isCurrentPrice());
    }

    public function test_add_new_price_closes_previous_price(): void
    {
        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $newPrice = $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-02-01',
            price: 55.00,
            createdBy: $this->user->id,
        );

        $this->assertDatabaseHas('newspaper_prices', [
            'newspaper_id' => $this->newspaper->id,
        ]);

        $this->assertCount(2, NewspaperPrice::where('newspaper_id', $this->newspaper->id)->get());

        $oldPrice = NewspaperPrice::where('newspaper_id', $this->newspaper->id)
            ->where('effective_from', '<', '2026-02-01')
            ->first();

        $this->assertNotNull($oldPrice);
        $this->assertEquals('2026-02-01', $oldPrice->effective_to->format('Y-m-d'));

        $this->assertTrue($newPrice->isCurrentPrice());
    }

    public function test_prevents_overlapping_price_periods(): void
    {
        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            effectiveTo: '2026-03-31',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A price period already exists for these dates.');

        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-02-01',
            effectiveTo: '2026-04-30',
            price: 55.00,
            createdBy: $this->user->id,
        );
    }

    public function test_get_price_for_date_returns_correct_price(): void
    {
        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            effectiveTo: '2026-03-31',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-04-01',
            price: 55.00,
            createdBy: $this->user->id,
        );

        $januaryPrice = $this->priceService->getPriceForDate($this->newspaper->id, '2026-02-15');
        $this->assertEquals(50.00, $januaryPrice->price);

        $aprilPrice = $this->priceService->getPriceForDate($this->newspaper->id, '2026-05-01');
        $this->assertEquals(55.00, $aprilPrice->price);

        $gapPrice = $this->priceService->getPriceForDate($this->newspaper->id, '2025-12-01');
        $this->assertNull($gapPrice);
    }

    public function test_preserves_old_price_after_new_price_added(): void
    {
        $oldPrice = $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            effectiveTo: '2026-03-31',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-04-01',
            price: 55.00,
            createdBy: $this->user->id,
        );

        $fetchedOldPrice = $this->priceService->getPriceForDate($this->newspaper->id, '2026-02-15');
        $this->assertEquals(50.00, $fetchedOldPrice->price);
        $this->assertEquals($oldPrice->id, $fetchedOldPrice->id);
    }

    public function test_get_current_price_returns_open_ended_price(): void
    {
        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            effectiveTo: '2026-03-31',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $currentPrice = $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-04-01',
            price: 55.00,
            createdBy: $this->user->id,
        );

        $fetchedCurrent = $this->priceService->getCurrentPrice($this->newspaper->id);
        $this->assertNotNull($fetchedCurrent);
        $this->assertEquals(55.00, $fetchedCurrent->price);
        $this->assertNull($fetchedCurrent->effective_to);
    }

    public function test_cannot_delete_current_active_price(): void
    {
        $price = $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete the current active price.');

        $this->priceService->deletePrice($price);
    }

    public function test_can_delete_closed_price(): void
    {
        $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-01-01',
            effectiveTo: '2026-03-31',
            price: 50.00,
            createdBy: $this->user->id,
        );

        $closedPrice = $this->priceService->addPrice(
            newspaperId: $this->newspaper->id,
            effectiveFrom: '2026-04-01',
            effectiveTo: '2026-06-30',
            price: 55.00,
            createdBy: $this->user->id,
        );

        $deletedId = $closedPrice->id;

        $this->assertTrue($this->priceService->deletePrice($closedPrice));
        $this->assertDatabaseMissing('newspaper_prices', ['id' => $deletedId]);
    }
}
