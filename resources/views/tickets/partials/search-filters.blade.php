<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-search"></i> Recherche Avancée
    </div>
    <div class="card-body">
        <form method="GET" action="{{ $searchAction }}">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label for="agent_id" class="form-label">
                        <i class="bi bi-person"></i> Agent
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <select name="agent_id" id="agent_id" class="form-select">
                            <option value="">Sélectionner un agent</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id_agent }}" @selected(($filters['agent_id'] ?? '') == $agent->id_agent)>
                                    {{ $agent->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="usine_id" class="form-label">
                        <i class="bi bi-building"></i> Usine
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                        <select name="usine_id" id="usine_id" class="form-select">
                            <option value="">Sélectionner une usine</option>
                            @foreach ($usines as $usine)
                                <option value="{{ $usine->id_usine }}" @selected(($filters['usine_id'] ?? '') == $usine->id_usine)>
                                    {{ $usine->nom_usine }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="date_debut" class="form-label">
                        <i class="bi bi-calendar3"></i> Date de début
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        <input type="date" name="date_debut" id="date_debut" class="form-control"
                            value="{{ $filters['date_debut'] ?? '' }}">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="date_fin" class="form-label">
                        <i class="bi bi-calendar-check"></i> Date de fin
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                        <input type="date" name="date_fin" id="date_fin" class="form-control"
                            value="{{ $filters['date_fin'] ?? '' }}">
                    </div>
                </div>

                @if (! empty($showStatutFilter))
                    <div class="col-lg-3 col-md-6">
                        <label for="statut" class="form-label">
                            <i class="bi bi-wallet2"></i> Statut paiement
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-wallet2"></i></span>
                            <select name="statut" id="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="solde" @selected(($filters['statut'] ?? '') === 'solde')>Soldé</option>
                                <option value="en_cours" @selected(($filters['statut'] ?? '') === 'en_cours')>En cours</option>
                            </select>
                        </div>
                    </div>
                @endif

                @if (! empty($showNumeroTicketFilter))
                    <div class="col-lg-3 col-md-6">
                        <label for="numero_ticket" class="form-label">
                            <i class="bi bi-tag"></i> N° Ticket
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <input type="text" name="numero_ticket" id="numero_ticket" class="form-control"
                                placeholder="Numéro du ticket"
                                value="{{ $filters['numero_ticket'] ?? '' }}">
                        </div>
                    </div>
                @endif

                <div class="col-12 d-flex flex-wrap gap-2">
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
