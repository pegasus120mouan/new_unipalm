@extends('layout.main')

@section('title', 'Effectuer un paiement')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Effectuer un paiement</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Paiements caisse</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @include('caisse.partials.payment-success-alert')

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-3">
        <div class="col-12">
            @include('caisse.partials.solde-badge', [
                'stats' => $stats,
                'badgeClass' => 'display-6',
                'modalId' => 'paiementsSoldeModal',
                'redirectTo' => route('caisse.paiements.index', ['tab' => $tab]),
            ])
        </div>
    </section>

    <section class="row mb-3">
        <div class="col-12">
            <div class="btn-group w-100 flex-wrap" role="group" aria-label="Types de paiement">
                <a href="{{ route('caisse.paiements.index', ['tab' => 'bordereaux']) }}"
                    class="btn {{ $tab === 'bordereaux' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-file-earmark-text"></i> Bordereaux
                </a>
                <a href="{{ route('caisse.paiements.index', ['tab' => 'demandes']) }}"
                    class="btn {{ $tab === 'demandes' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-clipboard-check"></i> Demandes
                </a>
                <a href="{{ route('caisse.paiements.index', ['tab' => 'divers']) }}"
                    class="btn {{ $tab === 'divers' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-box-arrow-right"></i> Divers
                </a>
            </div>
        </div>
    </section>

    @if ($tab === 'bordereaux')
        @include('caisse.paiements.partials.bordereaux-tab')
    @elseif ($tab === 'demandes')
        @include('caisse.paiements.partials.demandes-tab')
    @else
        @include('caisse.paiements.partials.divers-tab')
    @endif
@endsection

@if ($tab === 'bordereaux' && $bordereaux)
    @push('scripts')
        <script src="{{ asset('assets/js/amount-input.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function updateBordereauPaymentMax(select) {
                    var modalId = select.dataset.modalId;
                    var reste = parseFloat(select.dataset.reste) || 0;
                    var financement = parseFloat(select.dataset.financement) || 0;
                    var caisse = parseFloat(select.dataset.caisse) || 0;
                    var source = select.value;
                    var max = reste;

                    if (source === 'financement') {
                        max = Math.min(reste, Math.max(0, financement));
                    } else if (source === 'transactions') {
                        max = Math.min(reste, Math.max(0, caisse));
                    }

                    var maxLabel = document.getElementById(modalId + '_max_label');
                    if (maxLabel) {
                        maxLabel.textContent = new Intl.NumberFormat('fr-FR').format(max);
                    }

                    var chequeField = document.getElementById(modalId + '_cheque_field');
                    if (chequeField) {
                        chequeField.classList.toggle('d-none', source !== 'cheque');
                    }
                }

                document.querySelectorAll('.bordereau-payment-source').forEach(function (select) {
                    updateBordereauPaymentMax(select);
                    select.addEventListener('change', function () {
                        updateBordereauPaymentMax(select);
                    });
                });

                @if (old('payment_bordereau_id'))
                    var errorModal = document.getElementById('payerBordereau{{ old('payment_bordereau_id') }}');
                    if (errorModal) {
                        new bootstrap.Modal(errorModal).show();
                    }
                @endif
            });
        </script>
    @endpush
@endif

@if ($tab === 'demandes')
    @push('scripts')
        <script src="{{ asset('assets/js/amount-input.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if (old('payment_demande_id'))
                    var errorModal = document.getElementById('payerDemande{{ old('payment_demande_id') }}');
                    if (errorModal) {
                        new bootstrap.Modal(errorModal).show();
                    }
                @endif
            });
        </script>
    @endpush
@endif

@if ($tab === 'divers')
    @push('scripts')
        <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    @endpush
@endif
