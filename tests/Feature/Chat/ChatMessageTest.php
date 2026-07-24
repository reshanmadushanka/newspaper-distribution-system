<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatMessageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithChatPermission(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'use ai chat', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'tester', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guest_cannot_post_chat_message(): void
    {
        $this->postJson('/admin/chat/messages', ['message' => 'Hello'])
            ->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_post_chat_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/admin/chat/messages', ['message' => 'Hello'])
            ->assertForbidden();
    }

    public function test_user_with_permission_can_send_message_via_gateway(): void
    {
        Http::fake([
            '*/v1/chat' => Http::response([
                'conversation_id' => null,
                'reply' => 'Hello from AI',
                'actions' => [],
            ], 200),
        ]);

        config([
            'services.ai.url' => 'http://ai.test',
            'services.ai.token' => 'test-token',
        ]);

        $user = $this->userWithChatPermission();

        $response = $this->actingAs($user)
            ->postJson('/admin/chat/messages', ['message' => 'Hello']);

        $response->assertOk()
            ->assertJsonPath('reply', 'Hello from AI')
            ->assertJsonStructure(['conversation_id', 'reply', 'actions']);

        $this->assertDatabaseHas('chat_conversations', [
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('chat_messages', 2);
    }

    public function test_internal_tool_requires_service_token(): void
    {
        config(['services.ai.token' => 'secret-token']);

        $this->postJson('/internal/ai/tools/auto-generate/preview', [
            'user_id' => 1,
            'date' => '2026-07-17',
        ])->assertUnauthorized();
    }
}
