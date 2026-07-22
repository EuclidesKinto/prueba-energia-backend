<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CalculateController;
use App\Http\Controllers\Api\V1\ConsumptionController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function (): void {
    Route::post('/calculate', [CalculateController::class, 'store']);
    Route::get('/consumptions', [ConsumptionController::class, 'index']);
    Route::get('/prices', [PriceController::class, 'index']);
    Route::get('/health', HealthController::class);
});

// Alias direto na raiz, mantido por compatibilidade estrita com a especificação original (ADR-006).
Route::post('/calculate', [CalculateController::class, 'store']);
