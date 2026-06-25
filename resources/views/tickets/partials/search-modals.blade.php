@php
    $searchAction = $searchAction ?? route('tickets.index');
@endphp

<div class="modal fade" id="searchTicketModal" tabindex="-1" aria-labelledby="searchTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="searchTicketModalLabel">
                    <i class="bi bi-search"></i> Rechercher un ticket
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-primary btn-lg search-ticket-option"
                        data-target-modal="#searchByTicketModal">
                        <i class="bi bi-ticket-perforated"></i> Recherche par numero de ticket
                    </button>
                    <button type="button" class="btn btn-primary btn-lg search-ticket-option"
                        data-target-modal="#searchByAgentModal">
                        <i class="bi bi-person-badge"></i> Recherche par chargé de Mission
                    </button>
                    <button type="button" class="btn btn-primary btn-lg search-ticket-option"
                        data-target-modal="#searchByUsineModal">
                        <i class="bi bi-building"></i> Recherche par Usine
                    </button>
                    <button type="button" class="btn btn-primary btn-lg search-ticket-option"
                        data-target-modal="#searchByDateModal">
                        <i class="bi bi-calendar3"></i> Recherche par Date
                    </button>
                    <button type="button" class="btn btn-primary btn-lg search-ticket-option"
                        data-target-modal="#searchByDateRangeModal">
                        <i class="bi bi-calendar-range"></i> Recherche entre 2 dates
                    </button>
                    <button type="button" class="btn btn-primary btn-lg search-ticket-option"
                        data-target-modal="#searchByVehiculeModal">
                        <i class="bi bi-truck"></i> Recherche par Véhicule
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByTicketModal" tabindex="-1" aria-labelledby="searchByTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ $searchAction }}">
                <input type="hidden" name="search" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchByTicketModalLabel">
                        <i class="bi bi-ticket-perforated"></i> Recherche par Numéro de Ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <label for="search_numero_ticket" class="form-label fw-semibold">Numéro de Ticket</label>
                    <input type="text" name="numero_ticket" id="search_numero_ticket" class="form-control"
                        placeholder="Entrez le numéro du ticket" required autofocus>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByAgentModal" tabindex="-1" aria-labelledby="searchByAgentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ $searchAction }}">
                <input type="hidden" name="search" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchByAgentModalLabel">
                        <i class="bi bi-person-badge"></i> Recherche par chargé de Mission
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <label for="search_agent_id" class="form-label">Sélectionner un chargé de Mission</label>
                    <select name="agent_id" id="search_agent_id" class="form-select" required>
                        <option value="">Choisir un chargé de Mission</option>
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id_agent }}">{{ $agent->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByUsineModal" tabindex="-1" aria-labelledby="searchByUsineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ $searchAction }}">
                <input type="hidden" name="search" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchByUsineModalLabel">
                        <i class="bi bi-building"></i> Recherche par Usine
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <label for="search_usine_id" class="form-label">Sélectionner une Usine</label>
                    <select name="usine_id" id="search_usine_id" class="form-select" required>
                        <option value="">Choisir une usine</option>
                        @foreach ($usines as $usine)
                            <option value="{{ $usine->id_usine }}">{{ $usine->nom_usine }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByDateModal" tabindex="-1" aria-labelledby="searchByDateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ $searchAction }}">
                <input type="hidden" name="search" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchByDateModalLabel">
                        <i class="bi bi-calendar3"></i> Recherche par Date
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <label for="search_single_date" class="form-label">Sélectionner une date (date ticket)</label>
                    <input type="date" name="date_debut" id="search_single_date" class="form-control" required>
                    <input type="hidden" name="date_fin" id="search_single_date_mirror">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByDateRangeModal" tabindex="-1" aria-labelledby="searchByDateRangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ $searchAction }}">
                <input type="hidden" name="search" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchByDateRangeModalLabel">
                        <i class="bi bi-calendar-range"></i> Recherche entre 2 dates
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="search_date_debut" class="form-label">Date de début</label>
                        <input type="date" name="date_debut" id="search_date_debut" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label for="search_date_fin" class="form-label">Date de fin</label>
                        <input type="date" name="date_fin" id="search_date_fin" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="searchByVehiculeModal" tabindex="-1" aria-labelledby="searchByVehiculeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ $searchAction }}">
                <input type="hidden" name="search" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchByVehiculeModalLabel">
                        <i class="bi bi-truck"></i> Recherche par Véhicule
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <label for="search_vehicule_id" class="form-label">Sélectionner un Véhicule</label>
                    <select name="vehicule_id" id="search_vehicule_id" class="form-select" required>
                        <option value="">Choisir un véhicule</option>
                        @foreach ($vehicules as $vehicule)
                            <option value="{{ $vehicule->vehicules_id }}">{{ $vehicule->matricule_vehicule }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</div>
