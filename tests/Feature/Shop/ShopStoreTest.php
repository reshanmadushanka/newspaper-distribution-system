<?php

namespace Tests\Feature\Shop;

use App\Domain\Shops\Enums\ShopStatus;
use App\Domain\Shops\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'manage shops',
            'view shops',
            'create shops',
            'edit shops',
            'delete shops',
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

    public function test_store_creates_shop_with_valid_data(): void
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->user)
            ->post('/admin/shops', [
                'name' => 'Test Shop',
                'owner_name' => 'John Doe',
                'phone' => '0712345678',
                'email' => 'shop@example.com',
                'whatsapp_phone' => '0798765432',
                'address' => '123 Main St, City',
                'status' => ShopStatus::ACTIVE->value,
            ]);

        $response->assertRedirect('/admin/shops');
        $response->assertSessionHas('success', 'Shop created successfully.');

        $this->assertDatabaseHas('shops', [
            'name' => 'Test Shop',
            'owner_name' => 'John Doe',
            'phone' => '0712345678',
            'email' => 'shop@example.com',
            'whatsapp_phone' => '0798765432',
            'address' => '123 Main St, City',
            'status' => ShopStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseCount('shops', 1);
    }

}