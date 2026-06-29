<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VerifTicketController;
use App\Http\Controllers\Api\VerifUsineController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
});

Route::middleware(['verif.env', 'verif.api'])->prefix('verif')->group(function () {
    Route::get('/usines', [VerifUsineController::class, 'index']);
    Route::get('/mes_usines.php', [VerifUsineController::class, 'index']);

    Route::get('/tickets', [VerifTicketController::class, 'index']);
    Route::post('/tickets/{id_ticket}/verify', [VerifTicketController::class, 'markVerified'])
        ->whereNumber('id_ticket');
    Route::get('/mes_tickets.php', [VerifTicketController::class, 'index']);
});
