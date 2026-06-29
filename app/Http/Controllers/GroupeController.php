<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Groupe;
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
            ->whereHas('agents', fn ($query) => $query->whereNull('date_suppression'))
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
}
