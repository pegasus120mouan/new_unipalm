<?php

namespace App\Http\Controllers;

use App\Models\Groupe;
use Illuminate\View\View;

class GroupeController extends Controller
{
    public function index(): View
    {
        $groupes = Groupe::query()
            ->withCount('agents')
            ->has('agents')
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        return view('groupes.index', compact('groupes'));
    }

    public function show(Groupe $groupe): View
    {
        $agents = $groupe->agents()
            ->with('createur')
            ->orderByDesc('date_ajout')
            ->orderByDesc('id_agent')
            ->paginate(15)
            ->withQueryString();

        return view('groupes.show', compact('groupe', 'agents'));
    }
}
