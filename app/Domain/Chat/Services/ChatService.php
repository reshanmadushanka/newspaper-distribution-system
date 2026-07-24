<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        private AiGateway $aiGateway
    ) {}

    /**
     * @return array{conversation_id:int,reply:string,actions:array<int,array<string,mixed>>}
     */
    public function sendMessage(User $user, string $message, ?int $conversationId = null): array
    {
        $message = trim($message);

        $conversation = $this->resolveConversation($user, $conversationId, $message);
        $history = $this->buildHistory($conversation);

        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();

        $aiResponse = $this->aiGateway->chat([
            'user_id' => $user->id,
            'conversation_id' => (string) $conversation->id,
            'message' => $message,
            'locale' => $user->locale ?? app()->getLocale(),
            'history' => $history,
            'context' => [
                'permissions' => $permissions,
            ],
        ]);

        DB::transaction(function () use ($conversation, $message, $aiResponse): void {
            Message::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
            ]);

            Message::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiResponse['reply'],
                'actions' => $aiResponse['actions'] ?: null,
            ]);

            $conversation->touch();
        });

        return [
            'conversation_id' => $conversation->id,
            'reply' => $aiResponse['reply'],
            'actions' => $aiResponse['actions'],
        ];
    }

    /**
     * @return array{id:int,title:?string,messages:array<int,array{id:int,role:string,content:string,actions:?array,created_at:string}>}
     */
    public function getConversation(User $user, int $conversationId): array
    {
        $conversation = Conversation::query()
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($q) => $q->orderBy('id')])
            ->findOrFail($conversationId);

        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $conversation->messages->map(fn (Message $m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'actions' => $m->actions,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    private function resolveConversation(User $user, ?int $conversationId, string $message): Conversation
    {
        if ($conversationId) {
            return Conversation::query()
                ->where('user_id', $user->id)
                ->findOrFail($conversationId);
        }

        return Conversation::query()->create([
            'user_id' => $user->id,
            'title' => Str::limit($message, 80),
        ]);
    }

    /**
     * @return array<int, array{role:string,content:string}>
     */
    private function buildHistory(Conversation $conversation): array
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->limit(20)
            ->get(['role', 'content'])
            ->map(fn (Message $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->all();
    }
}
