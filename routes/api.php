<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OcrResultController;

Route::post('/ocr/result', [OcrResultController::class, 'store'])
    ->middleware('api.key');
