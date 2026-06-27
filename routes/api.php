<?php

use App\Http\Controllers\Api\VerifTicketController;
use App\Http\Controllers\Api\VerifUsineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['verif.env', 'verif.api'])->prefix('verif')->group(function () {
    Route::get('/usines', [VerifUsineController::class, 'index']);
    Route::get('/mes_usines.php', [VerifUsineController::class, 'index']);

    Route::get('/tickets', [VerifTicketController::class, 'index']);
    Route::post('/tickets/{id_ticket}/verify', [VerifTicketController::class, 'markVerified'])
        ->whereNumber('id_ticket');
    Route::get('/mes_tickets.php', [VerifTicketController::class, 'index']);
});
