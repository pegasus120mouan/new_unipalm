<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-funnel"></i> Critères de recherche
    </div>
    <div class="card-body">
        <form method="GET" action="{{ $searchAction }}">
            <input type="hidden" name="search" value="1">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label for="numero_ticket" class="form-label">Numéro de ticket</label>
                    <input type="text" name="numero_ticket" id="numero_ticket" class="form-control"
                        placeholder="Entrez un numéro de ticket"
                        value="{{ $filters['numero_ticket'] ?? '' }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="agent_id" class="form-label">Agent</label>
                    <select name="agent_id" id="agent_id" class="form-select">
                        <option value="">Tous les agents</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id_agent }}" @selected(($filters['agent_id'] ?? '') == $agent->id_agent)>
                                {{ $agent->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="usine_id" class="form-label">Usine</label>
                    <select name="usine_id" id="usine_id" class="form-select">
                        <option value="">Toutes les usines</option>
                        @foreach ($usines as $usine)
                            <option value="{{ $usine->id_usine }}" @selected(($filters['usine_id'] ?? '') == $usine->id_usine)>
                                {{ $usine->nom_usine }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="vehicule_id" class="form-label">Véhicule</label>
                    <select name="vehicule_id" id="vehicule_id" class="form-select">
                        <option value="">Tous les véhicules</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->vehicules_id }}" @selected(($filters['vehicule_id'] ?? '') == $vehicule->vehicules_id)>
                                {{ $vehicule->matricule_vehicule }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="date_debut" class="form-label">Date début</label>
                    <input type="date" name="date_debut" id="date_debut" class="form-control"
                        value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="date_fin" class="form-label">Date fin</label>
                    <input type="date" name="date_fin" id="date_fin" class="form-control"
                        value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-lg-6 col-md-12 d-flex align-items-end flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                    <a href="{{ $resetAction ?? $searchAction }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
