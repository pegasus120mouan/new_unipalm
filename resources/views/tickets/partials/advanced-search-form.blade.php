@php
    $selectedAgent = ($filters['agent_id'] ?? null)
        ? collect($agents ?? [])->firstWhere('id_agent', (int) $filters['agent_id'])
        : null;
    $agentsForAutocomplete = collect($agents ?? [])->map(fn ($agent) => [
        'id' => $agent->id_agent,
        'numero' => $agent->numero_agent ?? '',
        'name' => $agent->full_name,
    ])->values();
@endphp

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-funnel"></i> Critères de recherche
    </div>
    <div class="card-body">
        <form method="GET" action="{{ $searchAction }}" id="advancedSearchForm">
            <input type="hidden" name="search" value="1">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label for="numero_ticket" class="form-label">Numéro de ticket</label>
                    <input type="text" name="numero_ticket" id="numero_ticket" class="form-control"
                        placeholder="Entrez un numéro de ticket"
                        value="{{ $filters['numero_ticket'] ?? '' }}">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="search_agent_search" class="form-label">Agent</label>
                    <div class="position-relative">
                        <input type="text" id="search_agent_search" class="form-control"
                            placeholder="Tapez le nom ou le N° agent..."
                            value="{{ $selectedAgent?->full_name ?? '' }}"
                            autocomplete="off">
                        <input type="hidden" name="agent_id" id="search_agent_id"
                            value="{{ $filters['agent_id'] ?? '' }}">
                        <div id="search_agent_suggestions" class="list-group position-absolute w-100 shadow-sm"
                            style="z-index: 1050; display: none; max-height: 220px; overflow-y: auto;"></div>
                    </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const agents = @json($agentsForAutocomplete);
        const searchInput = document.getElementById('search_agent_search');
        const hiddenInput = document.getElementById('search_agent_id');
        const suggestions = document.getElementById('search_agent_suggestions');

        if (!searchInput || !hiddenInput || !suggestions) {
            return;
        }

        function clearSelection() {
            hiddenInput.value = '';
        }

        function selectAgent(agent) {
            searchInput.value = agent.name;
            hiddenInput.value = agent.id;
            suggestions.style.display = 'none';
        }

        function showSuggestions(query) {
            const term = query.trim().toLowerCase();
            suggestions.innerHTML = '';

            if (term.length < 1) {
                suggestions.style.display = 'none';
                clearSelection();
                return;
            }

            const matches = agents.filter((agent) =>
                (agent.name || '').toLowerCase().includes(term)
                || (agent.numero || '').toLowerCase().includes(term)
            ).slice(0, 10);

            if (matches.length === 0) {
                suggestions.innerHTML = '<div class="list-group-item text-muted">Aucun agent trouvé</div>';
                suggestions.style.display = 'block';
                clearSelection();
                return;
            }

            matches.forEach((agent) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.innerHTML = agent.numero
                    ? `<span class="text-muted">${agent.numero}</span> — <strong>${agent.name}</strong>`
                    : `<strong>${agent.name}</strong>`;
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    selectAgent(agent);
                });
                suggestions.appendChild(button);
            });

            suggestions.style.display = 'block';

            const exact = agents.find((agent) =>
                (agent.name || '').toLowerCase() === term
                || (agent.numero || '').toLowerCase() === term
            );
            if (exact) {
                hiddenInput.value = exact.id;
            } else if (!matches.some((agent) => String(agent.id) === String(hiddenInput.value))) {
                clearSelection();
            }
        }

        searchInput.addEventListener('input', () => {
            if (hiddenInput.value) {
                clearSelection();
            }
            showSuggestions(searchInput.value);
        });

        searchInput.addEventListener('focus', () => {
            if (searchInput.value.trim()) {
                showSuggestions(searchInput.value);
            }
        });

        document.addEventListener('click', (event) => {
            if (!searchInput.contains(event.target) && !suggestions.contains(event.target)) {
                suggestions.style.display = 'none';
            }
        });
    });
</script>
