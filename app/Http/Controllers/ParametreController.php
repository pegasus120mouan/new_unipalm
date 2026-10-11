<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Services\AgentBordereauPaymentService;
use App\Services\ParametrePurgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParametreController extends Controller
{
    public function __construct(
        private readonly AgentBordereauPaymentService $bordereauPaymentService,
        private readonly ParametrePurgeService $purgeService,
    ) {}

    public function index(): View
    {
        $this->authorizeHidden();

        return view('parametres.index', [
            'synthese' => $this->bordereauPaymentService->syntheseSoldes(),
            'compteurs' => $this->purgeService->compteurs(),
        ]);
    }

    public function solderBordereaux(): RedirectResponse
    {
        $this->authorizeHidden();

        $nombre = $this->bordereauPaymentService->solderTous();

        return redirect()
            ->route('parametres.index')
            ->with('success', $nombre > 0
                ? $nombre.' bordereau'.($nombre > 1 ? 'x ont été soldés' : ' a été soldé').'.'
                : 'Tous les bordereaux étaient déjà soldés.');
    }

    public function viderFinancements(): RedirectResponse
    {
        $this->authorizeHidden();

        $nombre = $this->purgeService->viderFinancements();

        return redirect()
            ->route('parametres.index')
            ->with('success', $nombre.' financement'.($nombre > 1 ? 's ont été supprimés' : ' a été supprimé').'.');
    }

    public function viderPrets(): RedirectResponse
    {
        $this->authorizeHidden();

        $nombre = $this->purgeService->viderPrets();

        return redirect()
            ->route('parametres.index')
            ->with('success', $nombre.' prêt'.($nombre > 1 ? 's ont été supprimés' : ' a été supprimé').'.');
    }

    public function viderBanques(): RedirectResponse
    {
        $this->authorizeHidden();

        $nombre = $this->purgeService->viderBanques();

        return redirect()
            ->route('parametres.index')
            ->with('success', $nombre.' banque'.($nombre > 1 ? 's ont été supprimées' : ' a été supprimée').'.');
    }

    public function viderCaisse(): RedirectResponse
    {
        $this->authorizeHidden();

        $this->purgeService->viderCaisse();

        return redirect()
            ->route('parametres.index')
            ->with('success', 'La caisse a été vidée. Le solde et le montant utilisable sont à 0.');
    }

    private function authorizeHidden(): void
    {
        $user = auth()->user();

        if (! $user instanceof Utilisateur || ! $user->isHiddenSuperuser()) {
            abort(403, 'Vous n\'avez pas accès à cette page.');
        }
    }
}
