<?php

namespace Tests\Feature\Newspapers;

use App\Domain\Newspapers\Enums\Language;
use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Enums\PublicationFrequency;
use App\Domain\Newspapers\Models\Newspaper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewspaperStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'manage newspapers',
            'view newspapers',
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
    }

    public function test_store_creates_newspaper_with_valid_data(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post('/admin/newspapers', [
                'name' => 'Daily Times',
                'publisher_name' => 'Times Publishing',
                'language' => Language::ENGLISH->value,
                'frequency' => PublicationFrequency::DAILY->value,
                'status' => NewspaperStatus::ACTIVE->value,
            ]);

        $response->assertRedirect('/admin/newspapers');
        $response->assertSessionHas('success', 'Newspaper created successfully.');

        $this->assertDatabaseHas('newspapers', [
            'name' => 'Daily Times',
            'publisher_name' => 'Times Publishing',
            'language' => Language::ENGLISH->value,
            'frequency' => 'daily',
            'status' => 'active',
        ]);
    }

    public function test_store_creates_newspaper_with_price(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post('/admin/newspapers', [
                'name' => 'Price Times',
                'frequency' => PublicationFrequency::DAILY->value,
                'status' => NewspaperStatus::ACTIVE->value,
                'price' => 75.00,
                'cost_price' => 50.00,
            ]);

        $response->assertRedirect('/admin/newspapers');

        $newspaper = Newspaper::where('name', 'Price Times')->first();
        $this->assertNotNull($newspaper);

        $this->assertDatabaseHas('newspapers', [
            'name' => 'Price Times',
            'price' => 75.00,
            'cost_price' => 50.00,
        ]);
    }

    public function test_store_creates_newspaper_without_price_when_not_provided(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post('/admin/newspapers', [
                'name' => 'No Price Paper',
                'frequency' => PublicationFrequency::WEEKLY->value,
                'status' => NewspaperStatus::ACTIVE->value,
            ]);

        $response->assertRedirect('/admin/newspapers');

        $newspaper = Newspaper::where('name', 'No Price Paper')->first();
        $this->assertNotNull($newspaper);
        $this->assertNull($newspaper->price);
        $this->assertNull($newspaper->cost_price);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/admin/newspapers', []);

        $response->assertSessionHasErrors(['name', 'frequency', 'status']);
    }

    public function test_store_validates_price_format(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/admin/newspapers', [
                'name' => 'Invalid Price Paper',
                'frequency' => PublicationFrequency::DAILY->value,
                'status' => NewspaperStatus::ACTIVE->value,
                'price' => -10,
            ]);

        $response->assertSessionHasErrors(['price']);
    }

    public function test_store_creates_newspaper_with_minimal_data(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post('/admin/newspapers', [
                'name' => 'Minimal Paper',
                'frequency' => PublicationFrequency::WEEKLY->value,
                'status' => NewspaperStatus::ACTIVE->value,
            ]);

        $response->assertRedirect('/admin/newspapers');

        $this->assertDatabaseHas('newspapers', [
            'name' => 'Minimal Paper',
            'frequency' => 'weekly',
        ]);
    }
}
