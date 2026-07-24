<?php

namespace App\Domain\Chat\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiGateway
{
    /**
     * @param  array{user_id:int,conversation_id:?string,message:string,locale?:string,history?:array<int,array{role:string,content:string}>,context?:array{permissions?:array<int,string>}}  $payload
     * @return array{conversation_id:?string,reply:string,actions:array<int,array<string,mixed>>}
     */
    public function chat(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.ai.url'), '/');
        $token = (string) config('services.ai.token');
        $timeout = (int) config('services.ai.timeout', 60);

        if ($baseUrl === '') {
            throw new RuntimeException('AI service URL is not configured.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($token)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout($timeout)
                ->post('/v1/chat', [
                    'user_id' => $payload['user_id'],
                    'conversation_id' => $payload['conversation_id'] ?? null,
                    'message' => $payload['message'],
                    'locale' => $payload['locale'] ?? 'en',
                    'history' => $payload['history'] ?? [],
                    'context' => $payload['context'] ?? ['permissions' => []],
                ])
                ->throw();
        } catch (ConnectionException $e) {
            $reason = $e->getMessage();
            $previous = $e->getPrevious()?->getMessage();
            if ($previous && ! str_contains($reason, $previous)) {
                $reason .= ' ('.$previous.')';
            }

            throw new RuntimeException(
                'AI service is unreachable at '.$baseUrl.'. '
                .'Start it with: cd ai-service && uvicorn app.main:app --reload --host 127.0.0.1 --port 8001. '
                .'Details: '.$reason,
                0,
                $e
            );
        } catch (RequestException $e) {
            $detail = $e->response?->json('detail');
            if (is_array($detail)) {
                // FastAPI validation errors: [{loc, msg, type}, ...]
                $detail = collect($detail)
                    ->map(fn ($item) => is_array($item) ? ($item['msg'] ?? json_encode($item)) : (string) $item)
                    ->implode('; ');
            }

            $message = $detail
                ?? $e->response?->json('message')
                ?? $e->response?->body()
                ?? $e->getMessage();

            if (is_string($message)) {
                $message = trim($message);
            } else {
                $message = json_encode($message) ?: $e->getMessage();
            }

            throw new RuntimeException('AI service error: '.$message, 0, $e);
        }

        $data = $response->json();

        return [
            'conversation_id' => $data['conversation_id'] ?? $payload['conversation_id'] ?? null,
            'reply' => (string) ($data['reply'] ?? ''),
            'actions' => is_array($data['actions'] ?? null) ? $data['actions'] : [],
        ];
    }
}
