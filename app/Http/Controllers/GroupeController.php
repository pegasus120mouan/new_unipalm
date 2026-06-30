<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Groupe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupeController extends Controller
{
    public function index(): View
    {
        $groupes = Groupe::query()
            ->withCount([
                'agents' => fn ($query) => $query->whereNull('date_suppression'),
                'agents as particuliers_count' => fn ($query) => $query
                    ->whereNull('date_suppression')
                    ->where('sous_groupe', Agent::SOUS_GROUPE_PARTICULIER),
                'agents as professionnels_count' => fn ($query) => $query
                    ->whereNull('date_suppression')
                    ->where('sous_groupe', Agent::SOUS_GROUPE_PROFESSIONNEL),
            ])
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        return view('groupes.index', compact('groupes'));
    }

    public function show(Request $request, Groupe $groupe): View
    {
        $sousGroupe = trim((string) $request->query('sous_groupe', ''));

        $agentsQuery = $groupe->agents()
            ->with('createur')
            ->whereNull('date_suppression');

        if (in_array($sousGroupe, [Agent::SOUS_GROUPE_PARTICULIER, Agent::SOUS_GROUPE_PROFESSIONNEL], true)) {
            $agentsQuery->where('sous_groupe', $sousGroupe);
        } else {
            $sousGroupe = '';
        }

        $agents = $agentsQuery
            ->orderByDesc('date_ajout')
            ->orderByDesc('id_agent')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'total' => $groupe->agents()->whereNull('date_suppression')->count(),
            'particuliers' => $groupe->agentsParticuliers()->whereNull('date_suppression')->count(),
            'professionnels' => $groupe->agentsProfessionnels()->whereNull('date_suppression')->count(),
        ];

        return view('groupes.show', compact('groupe', 'agents', 'sousGroupe', 'counts'));
    }

    public function updateCredentials(Request $request, Groupe $groupe): RedirectResponse
    {
        $requiresPassword = ! $groupe->hasCredentials();

        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:100',
                Rule::unique('chef_equipe', 'login')->ignore($groupe->id_chef, 'id_chef'),
            ],
            'password' => [$requiresPassword ? 'required' : 'nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $groupe->login = trim($validated['login']);

        if (! empty($validated['password'])) {
            $groupe->setPasswordFromPlain($validated['password']);
        }

        $groupe->save();

        return redirect()
            ->route('groupes.show', $groupe)
            ->with('success', 'Identifiants du chef d\'équipe mis à jour.');
    }
}
