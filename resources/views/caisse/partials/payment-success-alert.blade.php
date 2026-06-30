@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>{{ session('success') }}</span>
            @if (session('last_recu_id'))
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('recus.tickets.pdf', session('last_recu_id')) }}" target="_blank" class="btn btn-light btn-sm">
                        <i class="bi bi-printer"></i> Imprimer le reçu
                    </a>
                    <a href="{{ route('recus.tickets.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-receipt"></i> Voir tous les reçus
                    </a>
                </div>
            @endif
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif
