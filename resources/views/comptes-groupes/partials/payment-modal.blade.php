@php
    $montantTotal = (float) $stats['montant_total'];
    $montantPaye = (float) $stats['montant_paye'];
    $reste = (float) $stats['montant_du'];
    $maxPayable = min($reste, max(0, $soldeCaisse));
@endphp

<div class="modal fade" id="groupePaymentModal" tabindex="-1" aria-labelledby="groupePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="{{ route('comptes-groupes.paiement', $groupe) }}" id="groupePaymentForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 48px; height: 48px;">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="groupePaymentModalLabel">Effectuer un paiement</h5>
                            <p class="text-muted small mb-0">{{ $groupe->full_name }}</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body pt-3">
                    @if ($errors->has('paiement'))
                        <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>{{ $errors->first('paiement') }}</span>
                        </div>
                    @endif

                    <div class="card bg-primary bg-opacity-10 border-0 mb-4">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 text-primary">
                                <i class="bi bi-wallet2 fs-5"></i>
                                <span class="fw-semibold">Solde caisse disponible</span>
                            </div>
                            <span class="fs-5 fw-bold text-primary">{{ number_format($soldeCaisse, 0, '', ' ') }} FCFA</span>
                        </div>
                        @if ($soldeCaisse < $reste)
                            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                                <small class="text-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Solde insuffisant pour régler l'intégralité du montant dû.
                                </small>
                            </div>
                        @endif
                    </div>

                    <p class="text-uppercase text-muted small fw-semibold mb-2">Situation du compte</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 border-start border-4 border-secondary bg-light">
                                <div class="text-muted small">Montant total</div>
                                <div class="fw-bold mt-1">{{ number_format($montantTotal, 0, '', ' ') }} <small class="text-muted">FCFA</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 border-start border-4 border-success bg-light">
                                <div class="text-muted small">Déjà payé</div>
                                <div class="fw-bold text-success mt-1">{{ number_format($montantPaye, 0, '', ' ') }} <small>FCFA</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100 border-start border-4 border-danger bg-light">
                                <div class="text-muted small">Reste à payer</div>
                                <div class="fw-bold text-danger mt-1" id="groupeResteAPayer">{{ number_format($reste, 0, '', ' ') }} <small>FCFA</small></div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-3 p-3 mb-4">
                        <label for="montant_paiement_display" class="form-label fw-semibold mb-2">
                            Montant à payer
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white"><i class="bi bi-currency-exchange text-muted"></i></span>
                            <input type="text"
                                class="form-control fw-semibold @error('montant_paiement') is-invalid @enderror"
                                id="montant_paiement_display"
                                data-amount-input
                                inputmode="numeric"
                                placeholder="0"
                                value="{{ old('montant_paiement') ? number_format((float) old('montant_paiement'), 0, '', ' ') : '' }}"
                                autocomplete="off"
                                required>
                            <input type="hidden" name="montant_paiement" id="montant_paiement" data-amount-target
                                value="{{ old('montant_paiement') }}">
                            <span class="input-group-text bg-white fw-semibold">FCFA</span>
                        </div>
                        @error('montant_paiement')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                            <small class="text-muted">
                                Maximum : <strong>{{ number_format($maxPayable, 0, '', ' ') }} FCFA</strong>
                            </small>
                            <div class="d-flex flex-wrap gap-1">
                                @if ($maxPayable > 0)
                                    <button type="button" class="btn btn-outline-secondary btn-sm groupe-quick-amount" data-amount="{{ (int) $maxPayable }}">
                                        Tout payer
                                    </button>
                                @endif
                                @if ($maxPayable >= 2)
                                    <button type="button" class="btn btn-outline-secondary btn-sm groupe-quick-amount" data-amount="{{ (int) floor($maxPayable / 2) }}">
                                        50 %
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <p class="text-uppercase text-muted small fw-semibold mb-2">Aperçu après paiement</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="rounded-3 border p-3 h-100 text-center">
                                <div class="text-muted small mb-1">Reste à payer</div>
                                <div class="fw-bold text-danger" id="groupeNouveauReste">{{ number_format($reste, 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border p-3 h-100 text-center">
                                <div class="text-muted small mb-1">Nouveau total payé</div>
                                <div class="fw-bold text-success" id="groupeNouveauPaye">{{ number_format($montantPaye, 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="rounded-3 border p-3 h-100 text-center">
                                <div class="text-muted small mb-1">Nouveau solde caisse</div>
                                <div class="fw-bold text-primary" id="groupeNouveauSoldeCaisse">{{ number_format($soldeCaisse, 0, '', ' ') }} FCFA</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="motif_paiement" class="form-label">Motif / référence <span class="text-muted fw-normal">(optionnel)</span></label>
                        <input type="text" class="form-control" id="motif_paiement" name="motif_paiement"
                            value="{{ old('motif_paiement') }}" placeholder="Ex. Paiement partiel — mois de mai">
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success px-4" id="groupeBtnValiderPaiement" disabled>
                        <span class="groupe-btn-label">
                            <i class="bi bi-check2-circle me-1"></i> Confirmer le paiement
                        </span>
                        <span class="groupe-btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Traitement…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('assets/vendors/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/amount-input.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const display = document.getElementById('montant_paiement_display');
            const hidden = document.getElementById('montant_paiement');
            const btnValider = document.getElementById('groupeBtnValiderPaiement');
            const form = document.getElementById('groupePaymentForm');
            const totalDu = {{ $reste }};
            const totalPaye = {{ $montantPaye }};
            const soldeCaisse = {{ $soldeCaisse }};
            const maxPayable = Math.min(totalDu, soldeCaisse);
            const groupeName = @json($groupe->full_name);

            function formatMontant(nombre) {
                return window.UnipalmAmount?.format(nombre) ?? String(nombre).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }

            function parseMontant() {
                return window.UnipalmAmount?.parse(hidden?.value) ?? (parseInt(hidden?.value, 10) || 0);
            }

            function setMontant(nombre) {
                if (!hidden || !display) return;
                hidden.value = nombre > 0 ? String(nombre) : '';
                display.value = nombre > 0 ? formatMontant(nombre) : '';
                updateApercu();
            }

            function updateApercu() {
                const montant = parseMontant();
                let erreur = '';

                if (montant > soldeCaisse) {
                    erreur = 'Solde caisse insuffisant';
                } else if (montant > totalDu) {
                    erreur = 'Montant supérieur au reste à payer';
                }

                const nouveauReste = Math.max(0, totalDu - montant);
                const nouveauPaye = totalPaye + montant;
                const nouveauSolde = soldeCaisse - montant;
                const nouveauResteEl = document.getElementById('groupeNouveauReste');

                if (!nouveauResteEl) return;

                if (erreur) {
                    nouveauResteEl.className = 'fw-bold text-danger';
                    nouveauResteEl.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>' + erreur;
                    btnValider.disabled = true;
                } else if (nouveauReste === 0 && montant > 0) {
                    nouveauResteEl.className = 'fw-bold text-success';
                    nouveauResteEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>Compte soldé';
                    btnValider.disabled = false;
                } else {
                    nouveauResteEl.className = 'fw-bold text-danger';
                    nouveauResteEl.textContent = formatMontant(nouveauReste) + ' FCFA';
                    btnValider.disabled = montant <= 0;
                }

                document.getElementById('groupeNouveauPaye').textContent = formatMontant(nouveauPaye) + ' FCFA';
                document.getElementById('groupeNouveauSoldeCaisse').textContent = formatMontant(nouveauSolde) + ' FCFA';
            }

            display?.addEventListener('input', updateApercu);

            document.querySelectorAll('.groupe-quick-amount').forEach(function (button) {
                button.addEventListener('click', function () {
                    setMontant(parseInt(button.dataset.amount, 10) || 0);
                });
            });

            form?.addEventListener('submit', function (e) {
                e.preventDefault();

                const montant = parseMontant();

                if (montant <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Montant invalide',
                        text: 'Veuillez saisir un montant supérieur à zéro.',
                        confirmButtonColor: '#198754',
                    });
                    return;
                }

                if (montant > maxPayable) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Montant trop élevé',
                        text: 'Le montant ne peut pas dépasser ' + formatMontant(maxPayable) + ' FCFA.',
                        confirmButtonColor: '#198754',
                    });
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: 'Confirmer le paiement',
                    html: '<p class="mb-2">Vous allez effectuer un paiement de</p>' +
                        '<p class="fs-4 fw-bold text-success mb-2">' + formatMontant(montant) + ' FCFA</p>' +
                        '<p class="text-muted small mb-0">pour <strong>' + groupeName + '</strong></p>',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-check2"></i> Valider',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true,
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    btnValider.disabled = true;
                    btnValider.querySelector('.groupe-btn-label')?.classList.add('d-none');
                    btnValider.querySelector('.groupe-btn-loading')?.classList.remove('d-none');
                    form.submit();
                });
            });

            updateApercu();

            @if ($errors->has('paiement') || $errors->has('montant_paiement'))
                new bootstrap.Modal(document.getElementById('groupePaymentModal')).show();
            @endif
        });
    </script>
@endpush
