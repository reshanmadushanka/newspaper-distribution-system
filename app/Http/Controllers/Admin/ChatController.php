<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Chat\Services\ChatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:4000'],
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
        ]);

        $user = Auth::user();
        if (! $user) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        try {
            $result = $this->chatService->sendMessage(
                $user,
                $validated['message'],
                $validated['conversation_id'] ?? null
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json($result);
    }

    public function show(int $conversationId): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        return response()->json(
            $this->chatService->getConversation($user, $conversationId)
        );
    }
}
