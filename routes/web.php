<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BordereauController;
use App\Http\Controllers\CompteAgentController;
use App\Http\Controllers\CompteGroupeController;
use App\Http\Controllers\FinancementController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\PretController;
use App\Http\Controllers\PrixUnitaireController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UsineController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\VehiculeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('tickets.index'));

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/jour', [TicketController::class, 'today'])->name('tickets.today');
    Route::get('/tickets/en-attente', [TicketController::class, 'pending'])->name('tickets.pending');
    Route::get('/tickets/valides', [TicketController::class, 'validated'])->name('tickets.validated');
    Route::get('/tickets/payes', [TicketController::class, 'paid'])->name('tickets.paid');
    Route::get('/tickets/modifications', [TicketController::class, 'modifications'])->name('tickets.modifications');
    Route::get('/tickets/recherche', [TicketController::class, 'search'])->name('tickets.search');
    Route::put('/tickets/{ticket:id_ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket:id_ticket}/validate', [TicketController::class, 'validate'])->name('tickets.validate');
    Route::post('/tickets/validate-bulk', [TicketController::class, 'validateBulk'])->name('tickets.validate-bulk');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

    Route::get('/financements', [FinancementController::class, 'index'])->name('financements.index');
    Route::get('/financements/agents/{agent:id_agent}', [FinancementController::class, 'show'])->name('financements.show');
    Route::get('/financements/agents/{agent:id_agent}/pdf', [FinancementController::class, 'pdf'])->name('financements.pdf');
    Route::post('/financements', [FinancementController::class, 'store'])->name('financements.store');

    Route::get('/prets', [PretController::class, 'index'])->name('prets.index');
    Route::get('/prets/agents/{agent:id_agent}', [PretController::class, 'show'])->name('prets.show');
    Route::post('/prets', [PretController::class, 'store'])->name('prets.store');

    Route::get('/comptes-agents', [CompteAgentController::class, 'index'])->name('comptes-agents.index');
    Route::get('/comptes-agents/{agent:id_agent}', [CompteAgentController::class, 'show'])->name('comptes-agents.show');
    Route::post('/comptes-agents/bordereaux/{bordereau:id_bordereau}/paiement', [CompteAgentController::class, 'storeBordereauPayment'])
        ->name('comptes-agents.bordereaux.payment');
    Route::get('/comptes-agents/{agent:id_agent}/historique-transactions/pdf', [CompteAgentController::class, 'transactionsHistoryPdf'])
        ->name('comptes-agents.transactions.pdf');

    Route::get('/comptes-groupes', [CompteGroupeController::class, 'index'])->name('comptes-groupes.index');
    Route::get('/comptes-groupes/{groupe:id_chef}', [CompteGroupeController::class, 'show'])->name('comptes-groupes.show');
    Route::post('/comptes-groupes/{groupe:id_chef}/paiement', [CompteGroupeController::class, 'pay'])->name('comptes-groupes.paiement');

    Route::get('/groupes', [GroupeController::class, 'index'])->name('groupes.index');
    Route::get('/groupes/{groupe}', [GroupeController::class, 'show'])->name('groupes.show');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::get('/agents/{agent:id_agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::put('/agents/{agent:id_agent}', [AgentController::class, 'update'])->name('agents.update');
    Route::patch('/agents/{agent:id_agent}/inline', [AgentController::class, 'inlineUpdate'])->name('agents.inline-update');
    Route::delete('/agents/{agent:id_agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

    Route::get('/usines', [UsineController::class, 'index'])->name('usines.index');
    Route::post('/usines', [UsineController::class, 'store'])->name('usines.store');
    Route::get('/montants-usines', [UsineController::class, 'amounts'])->name('usines.amounts');
    Route::get('/montants-usines/{usine:id_usine}', [UsineController::class, 'amountsShow'])->name('usines.amounts.show');
    Route::get('/montants-usines/{usine:id_usine}/historique-paiements/pdf', [UsineController::class, 'paymentsHistoryPdf'])
        ->name('usines.amounts.payments.pdf');
    Route::post('/montants-usines/{usine:id_usine}/paiement', [UsineController::class, 'storePayment'])->name('usines.amounts.payment');

    Route::get('/vehicules', [VehiculeController::class, 'index'])->name('vehicules.index');
    Route::post('/vehicules', [VehiculeController::class, 'store'])->name('vehicules.store');

    Route::get('/utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
    Route::post('/utilisateurs', [UtilisateurController::class, 'store'])->name('utilisateurs.store');
    Route::post('/utilisateurs/{utilisateur}/statut', [UtilisateurController::class, 'toggleStatut'])->name('utilisateurs.toggle-statut');

    Route::get('/prix-unitaires', [PrixUnitaireController::class, 'index'])->name('prix-unitaires.index');
    Route::post('/prix-unitaires', [PrixUnitaireController::class, 'store'])->name('prix-unitaires.store');

    Route::get('/bordereaux', [BordereauController::class, 'index'])->name('bordereaux.index');
    Route::post('/bordereaux/preview', [BordereauController::class, 'preview'])->name('bordereaux.preview');
    Route::post('/bordereaux', [BordereauController::class, 'store'])->name('bordereaux.store');
    Route::post('/bordereaux/{bordereau}/validate', [BordereauController::class, 'validate'])->name('bordereaux.validate');
    Route::delete('/bordereaux/{bordereau}', [BordereauController::class, 'destroy'])->name('bordereaux.destroy');
    Route::get('/bordereaux/{numero}/pdf', [BordereauController::class, 'pdf'])
        ->where('numero', 'BORD-[0-9\-]+')
        ->name('bordereaux.pdf');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
