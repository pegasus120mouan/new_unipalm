<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Vehicule;
use Illuminate\Http\JsonResponse;

class VerifCatalogController extends Controller
{
    public function agents(): JsonResponse
    {
        $agents = Agent::query()
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id_agent', 'nom', 'prenom'])
            ->map(fn (Agent $agent) => [
                'id_agent' => $agent->id_agent,
                'nom' => $agent->nom,
                'prenom' => $agent->prenom,
            ])
            ->values()
            ->all();

        return response()->json([
            'agents' => $agents,
        ]);
    }

    public function vehicules(): JsonResponse
    {
        $vehicules = Vehicule::query()
            ->orderBy('matricule_vehicule')
            ->get(['vehicules_id', 'matricule_vehicule', 'type_vehicule'])
            ->map(fn (Vehicule $vehicule) => [
                'vehicules_id' => $vehicule->vehicules_id,
                'matricule_vehicule' => $vehicule->matricule_vehicule,
                'type_vehicule' => $vehicule->type_vehicule,
                'type_label' => $vehicule->type_label,
            ])
            ->values()
            ->all();

        return response()->json([
            'vehicules' => $vehicules,
        ]);
    }
}
