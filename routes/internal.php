<?php

use App\Http\Controllers\Internal\AiToolController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal AI tool routes (Python service → Laravel)
|--------------------------------------------------------------------------
|
| Authenticated with AI_SERVICE_TOKEN only. Not for browser use.
|
*/

Route::prefix('internal/ai')
    ->middleware(['ai.service'])
    ->group(function (): void {
        Route::post('tools/auto-generate/preview', [AiToolController::class, 'previewAutoGenerate'])
            ->name('internal.ai.auto-generate.preview');
        Route::post('tools/auto-generate/start', [AiToolController::class, 'startAutoGenerate'])
            ->name('internal.ai.auto-generate.start');
        Route::get('tools/auto-generate/progress', [AiToolController::class, 'progressAutoGenerate'])
            ->name('internal.ai.auto-generate.progress');
    });
