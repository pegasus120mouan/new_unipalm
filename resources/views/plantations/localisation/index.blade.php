@extends('layout.main')

@section('title', 'Localisation des plantations')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3>Localisation des plantations</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('plantations.index') }}">Plantations</a></li>
                <li class="breadcrumb-item active" aria-current="page">Localisation</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if ($loadError)
        <div class="alert alert-danger" role="alert">{{ $loadError }}</div>
    @endif

    @php
        $withGeo = collect($regions)->where('has_geojson', true)->count();
    @endphp

    <section class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ count($regions) }}</div>
                    <div class="small opacity-75">Régions</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card border-0 text-white h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $withGeo }}</div>
                    <div class="small opacity-75">Avec délimitation GeoJSON</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <label for="searchInput" class="form-label">Rechercher une région</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Ex. Lôh-Djiboua...">
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-geo-alt-fill me-1"></i> Liste des régions</span>
                    <span class="badge bg-primary" id="regionsCountBadge">{{ count($regions) }}</span>
                </div>
                <div class="card-body p-0">
                    @if (empty($regions))
                        <div class="text-center py-5">
                            <i class="bi bi-map fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Aucune région trouvée dans la base locale</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="localisation-table-header">
                                    <tr>
                                        <th>Code</th>
                                        <th>Région</th>
                                        <th>Délimitation</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="regionsTbody">
                                    @foreach ($regions as $region)
                                        @php
                                            $regionId = $region['id'] ?? null;
                                            $regionNom = $region['nom'] ?? ('Région #'.$regionId);
                                            $hasGeo = ! empty($region['has_geojson']);
                                        @endphp
                                        <tr class="region-row"
                                            data-name="{{ \Illuminate\Support\Str::lower($regionNom.' '.($region['code'] ?? '')) }}">
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $region['code'] ?? '—' }}
                                                </span>
                                            </td>
                                            <td class="fw-semibold">{{ $regionNom }}</td>
                                            <td>
                                                @if ($hasGeo)
                                                    <span class="badge bg-success">Délimitation disponible</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Sans GeoJSON</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($regionId)
                                                    <a href="{{ route('plantations.localisation.show', $regionId) }}"
                                                        class="btn btn-sm btn-outline-primary"
                                                        target="_blank"
                                                        rel="noopener noreferrer">
                                                        <i class="bi bi-map"></i> Voir sur la carte
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="noResults" class="text-center py-5 d-none">
                            <i class="bi bi-map fs-1 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Aucune région trouvée</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <style>
        .localisation-table-header {
            background: #111;
        }
        .localisation-table-header th {
            color: #fff !important;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            white-space: nowrap;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const rows = Array.from(document.querySelectorAll('.region-row'));
            const noResults = document.getElementById('noResults');
            const badge = document.getElementById('regionsCountBadge');
            const tableWrapper = document.querySelector('.table-responsive');

            if (!searchInput || !rows.length) return;

            searchInput.addEventListener('input', function () {
                const search = searchInput.value.toLowerCase().trim();
                let visible = 0;
                rows.forEach(function (row) {
                    const match = !search || (row.dataset.name || '').includes(search);
                    row.classList.toggle('d-none', !match);
                    if (match) visible += 1;
                });
                if (badge) badge.textContent = String(visible);
                if (tableWrapper) tableWrapper.classList.toggle('d-none', visible === 0);
                if (noResults) noResults.classList.toggle('d-none', visible > 0);
            });
        });
    </script>
@endpush
