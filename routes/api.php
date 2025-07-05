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

Route::get('/php-user', function () {
    return get_current_user(); // or: exec('whoami');
});
