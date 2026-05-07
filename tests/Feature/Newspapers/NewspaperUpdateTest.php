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

class NewspaperUpdateTest extends TestCase
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
    }

    public function test_update_changes_newspaper_fields(): void
    {
        $newspaper = Newspaper::create([
            'name' => 'Old Name',
            'frequency' => PublicationFrequency::DAILY->value,
            'status' => NewspaperStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/admin/newspapers/{$newspaper->id}", [
                'name' => 'New Name',
                'frequency' => PublicationFrequency::WEEKLY->value,
                'status' => NewspaperStatus::ACTIVE->value,
            ]);

        $response->assertRedirect('/admin/newspapers');
        $response->assertSessionHas('success', 'Newspaper updated successfully.');

        $this->assertDatabaseHas('newspapers', [
            'id' => $newspaper->id,
            'name' => 'New Name',
            'frequency' => 'weekly',
        ]);
    }

    public function test_update_validates_required_fields(): void
    {
        $newspaper = Newspaper::create([
            'name' => 'Test Paper',
            'frequency' => PublicationFrequency::DAILY->value,
            'status' => NewspaperStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/admin/newspapers/{$newspaper->id}", [
                'name' => '',
            ]);

        $response->assertSessionHasErrors(['name', 'frequency', 'status']);
    }
}
