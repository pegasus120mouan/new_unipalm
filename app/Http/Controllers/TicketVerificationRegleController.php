<?php

namespace App\Http\Controllers;

use App\Models\TicketParametre;
use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketVerificationRegleController extends Controller
{
    public function edit(): View
    {
        $this->authorizeHidden();

        return view('tickets.verification-regle', [
            'actif' => TicketParametre::requiresVerification(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeHidden();

        $validated = $request->validate([
            'exiger_verification' => ['required', 'in:0,1'],
        ]);

        $actif = $validated['exiger_verification'] === '1';
        TicketParametre::setRequiresVerification($actif);

        return redirect()
            ->route('tickets.verification-regle')
            ->with('success', $actif
                ? 'Vérification obligatoire : un ticket doit être vérifié avant d\'entrer dans un bordereau.'
                : 'Vérification facultative : un bordereau peut être généré même si le ticket n\'est pas vérifié.');
    }

    private function authorizeHidden(): void
    {
        $user = auth()->user();

        if (! $user instanceof Utilisateur || ! $user->isHiddenSuperuser()) {
            abort(403, 'Vous n\'avez pas accès à ce réglage.');
        }
    }
}
