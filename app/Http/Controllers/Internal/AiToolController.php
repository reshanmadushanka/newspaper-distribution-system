<?php

namespace App\Http\Controllers\Internal;

use App\Domain\Invoices\Services\InvoiceService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiToolController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    public function previewAutoGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        if (! $this->canAutoGenerate($user)) {
            return response()->json([
                'error' => 'permission_denied',
                'message' => 'User lacks auto generate invoice permission.',
            ], 403);
        }

        $targetDate = $validated['date'];
        $lastWeekDate = date('Y-m-d', strtotime($targetDate.' - 7 days'));
        $shops = $this->invoiceService->getShopsWithLastWeekInvoicesButNotForDate($targetDate);

        return response()->json([
            'target_date' => $targetDate,
            'last_week_date' => $lastWeekDate,
            'eligible_shops_count' => $shops->count(),
            'shops' => $shops->map(fn ($shop) => [
                'id' => $shop->id,
                'name' => $shop->name,
            ])->values(),
        ]);
    }

    public function startAutoGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        if (! $this->canAutoGenerate($user)) {
            return response()->json([
                'error' => 'permission_denied',
                'message' => 'User lacks auto generate invoice permission.',
            ], 403);
        }

        $previewShops = $this->invoiceService->getShopsWithLastWeekInvoicesButNotForDate($validated['date']);
        if ($previewShops->isEmpty()) {
            return response()->json([
                'error' => 'no_eligible_shops',
                'message' => 'No eligible shops for the selected date.',
                'target_date' => $validated['date'],
                'eligible_shops_count' => 0,
            ], 422);
        }

        $result = $this->invoiceService->dispatchInvoiceGeneration(
            $validated['date'],
            $user->id
        );

        return response()->json([
            'message' => $result['message'],
            'total_shops' => $result['total_shops'],
            'target_date' => $result['target_date'],
            'status' => 'processing',
        ]);
    }

    public function progressAutoGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $progress = $this->invoiceService->getGenerationProgress((int) $validated['user_id']);

        return response()->json($progress);
    }

    private function canAutoGenerate(User $user): bool
    {
        return $user->can('auto generate invoice') || $user->can('manage invoices');
    }
}
