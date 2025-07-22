<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/bank/upload', [\App\Http\Controllers\BankStatementController::class, 'handleUpload'])->name('bank.upload');


Route::post('/test', function () {
    return 'hello';
});

Route::post('/signs', [\App\Http\Controllers\PgpEncryptController::class, 'handlePgp']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    // If the request makes it here, the token is valid.
    // Return the authenticated user's data.
    \Illuminate\Support\Facades\Log::info('Laravel received token validation request from Spring Boot', [
        'user' => $request->user(),
    ]);
    return $request->user();
});
