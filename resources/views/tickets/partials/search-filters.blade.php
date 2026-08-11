@php
    $agentLabel = $agentLabel ?? 'Agent';
    $agentsForFilterAutocomplete = collect($agents ?? [])->map(fn ($agent) => [
        'id' => $agent->id_agent,
        'numero' => $agent->numero_agent ?? '',
        'name' => $agent->full_name,
    ])->values();
    $usinesForFilterAutocomplete = collect($usines ?? [])->map(fn ($usine) => [
        'id' => $usine->id_usine,
        'label' => $usine->nom_usine,
    ])->values();
    $vehiculesForFilterAutocomplete = collect($vehicules ?? [])->map(fn ($vehicule) => [
        'id' => $vehicule->vehicules_id,
        'label' => $vehicule->matricule_vehicule,
    ])->values();

    $selectedAgent = ($filters['agent_id'] ?? null)
        ? collect($agents ?? [])->firstWhere('id_agent', (int) $filters['agent_id'])
        : null;
    $selectedUsine = ($filters['usine_id'] ?? null)
        ? collect($usines ?? [])->firstWhere('id_usine', (int) $filters['usine_id'])
        : null;
    $selectedVehicule = ($filters['vehicule_id'] ?? null)
        ? collect($vehicules ?? [])->firstWhere('vehicules_id', (int) $filters['vehicule_id'])
        : null;
@endphp

<div class="card mb-4" id="ticketsSearchFilters">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-search"></i> Recherche Avancée
    </div>
    <div class="card-body">
        <form method="GET" action="{{ $searchAction }}" id="ticketsSearchFiltersForm">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label for="filter_agent_search" class="form-label">
                        <i class="bi bi-person"></i> {{ $agentLabel }}
                    </label>
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="filter_agent_search" class="form-control"
                                placeholder="Tapez le nom..."
                                value="{{ $selectedAgent?->full_name ?? '' }}"
                                autocomplete="off">
                        </div>
                        <input type="hidden" name="agent_id" id="filter_agent_id"
                            value="{{ $filters['agent_id'] ?? '' }}">
                        <div id="filter_agent_suggestions" class="list-group position-absolute w-100 shadow-sm"
                            style="z-index: 1050; display: none; max-height: 220px; overflow-y: auto;"></div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="filter_usine_search" class="form-label">
                        <i class="bi bi-building"></i> Usine
                    </label>
                    <div class="position-relative">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <input type="text" id="filter_usine_search" class="form-control"
                                placeholder="Rechercher une usine..."
                                value="{{ $selectedUsine?->nom_usine ?? '' }}"
                                autocomplete="off">
                        </div>
                        <input type="hidden" name="usine_id" id="filter_usine_id"
                            value="{{ $filters['usine_id'] ?? '' }}">
                        <div id="filter_usine_suggestions" class="list-group position-absolute w-100 shadow-sm"
                            style="z-index: 1050; display: none; max-height: 220px; overflow-y: auto;"></div>
                    </div>
                </div>

                @if (! empty($showVehiculeFilter))
                    <div class="col-lg-3 col-md-6">
                        <label for="filter_vehicule_search" class="form-label">
                            <i class="bi bi-truck"></i> Véhicule
                        </label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-truck"></i></span>
                                <input type="text" id="filter_vehicule_search" class="form-control"
                                    placeholder="Rechercher un véhicule..."
                                    value="{{ $selectedVehicule?->matricule_vehicule ?? '' }}"
                                    autocomplete="off">
                            </div>
                            <input type="hidden" name="vehicule_id" id="filter_vehicule_id"
                                value="{{ $filters['vehicule_id'] ?? '' }}">
                            <div id="filter_vehicule_suggestions" class="list-group position-absolute w-100 shadow-sm"
                                style="z-index: 1050; display: none; max-height: 220px; overflow-y: auto;"></div>
                        </div>
                    </div>
                @endif

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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function setupFilterAutocomplete(config) {
            const searchInput = document.getElementById(config.searchId);
            const hiddenInput = document.getElementById(config.hiddenId);
            const suggestions = document.getElementById(config.suggestionsId);

            if (!searchInput || !hiddenInput || !suggestions) {
                return;
            }

            function clearSelection() {
                hiddenInput.value = '';
            }

            function selectItem(item) {
                searchInput.value = config.getLabel ? config.getLabel(item) : item[config.labelKey];
                hiddenInput.value = item.id;
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

                const matches = config.items.filter((item) => config.filter(item, term)).slice(0, 10);

                if (matches.length === 0) {
                    suggestions.innerHTML = `<div class="list-group-item text-muted">${config.emptyText}</div>`;
                    suggestions.style.display = 'block';
                    clearSelection();
                    return;
                }

                matches.forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.innerHTML = config.renderItem(item);
                    button.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        selectItem(item);
                    });
                    suggestions.appendChild(button);
                });

                suggestions.style.display = 'block';

                const exact = config.items.find((item) => config.isExact(item, term));
                if (exact) {
                    hiddenInput.value = exact.id;
                } else if (!matches.some((item) => String(item.id) === String(hiddenInput.value))) {
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
                const term = searchInput.value.trim();
                if (term && !hiddenInput.value) {
                    showSuggestions(term);
                }
            });

            document.addEventListener('click', (event) => {
                if (!searchInput.contains(event.target) && !suggestions.contains(event.target)) {
                    suggestions.style.display = 'none';
                }
            });
        }

        setupFilterAutocomplete({
            items: @json($agentsForFilterAutocomplete),
            searchId: 'filter_agent_search',
            hiddenId: 'filter_agent_id',
            suggestionsId: 'filter_agent_suggestions',
            labelKey: 'name',
            emptyText: 'Aucun résultat',
            getLabel: (item) => item.numero ? `${item.numero} — ${item.name}` : item.name,
            filter: (item, term) => (item.numero || '').toLowerCase().includes(term)
                || (item.name || '').toLowerCase().includes(term),
            isExact: (item, term) => (item.numero || '').toLowerCase() === term
                || (item.name || '').toLowerCase() === term,
            renderItem: (item) => item.numero
                ? `<span class="text-muted">${item.numero}</span> — <strong>${item.name}</strong>`
                : `<strong>${item.name}</strong>`,
        });

        setupFilterAutocomplete({
            items: @json($usinesForFilterAutocomplete),
            searchId: 'filter_usine_search',
            hiddenId: 'filter_usine_id',
            suggestionsId: 'filter_usine_suggestions',
            labelKey: 'label',
            emptyText: 'Aucune usine trouvée',
            filter: (item, term) => (item.label || '').toLowerCase().includes(term),
            isExact: (item, term) => (item.label || '').toLowerCase() === term,
            renderItem: (item) => `<strong>${item.label}</strong>`,
        });

        @if (! empty($showVehiculeFilter))
        setupFilterAutocomplete({
            items: @json($vehiculesForFilterAutocomplete),
            searchId: 'filter_vehicule_search',
            hiddenId: 'filter_vehicule_id',
            suggestionsId: 'filter_vehicule_suggestions',
            labelKey: 'label',
            emptyText: 'Aucun véhicule trouvé',
            filter: (item, term) => (item.label || '').toLowerCase().includes(term),
            isExact: (item, term) => (item.label || '').toLowerCase() === term,
            renderItem: (item) => `<strong>${item.label}</strong>`,
        });
        @endif
    });
</script>
