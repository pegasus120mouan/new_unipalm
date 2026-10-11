@extends('layout.main')

@section('title', 'Paramètres')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Paramètres</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Paramètres</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-text"></i> Bordereaux
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Solder tous les bordereaux générés dans la base. Chaque bordereau encore ouvert passe au statut soldé, et ses tickets sont soldés du même montant. La caisse n'est pas débitée.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Bordereaux générés</div>
                                <div class="fs-4 fw-semibold">{{ number_format($synthese['total'], 0, ',', ' ') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Déjà soldés</div>
                                <div class="fs-4 fw-semibold text-success">{{ number_format($synthese['deja_soldes'], 0, ',', ' ') }}</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Reste à solder</div>
                                <div class="fs-4 fw-semibold text-danger">{{ number_format($synthese['a_solder'], 0, ',', ' ') }}</div>
                                <div class="small text-muted">{{ number_format($synthese['reste'], 0, ',', ' ') }} FCFA</div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#solderBordereauxModal"
                        @disabled($synthese['a_solder'] < 1)>
                        <i class="bi bi-check2-all"></i> Solder tous les bordereaux
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-trash"></i> Vidage des tables
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Chaque bouton efface les enregistrements de la table correspondante. Les tickets et les bordereaux restent en place.
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                <div class="text-muted small">Financements</div>
                                <div class="fs-4 fw-semibold mb-3">{{ number_format($compteurs['financements'], 0, ',', ' ') }}</div>
                                <button type="button" class="btn btn-outline-danger mt-auto" data-bs-toggle="modal" data-bs-target="#viderFinancementsModal"
                                    @disabled($compteurs['financements'] < 1)>
                                    Vider les financements
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                <div class="text-muted small">Prêts agents</div>
                                <div class="fs-4 fw-semibold mb-3">{{ number_format($compteurs['prets'], 0, ',', ' ') }}</div>
                                <button type="button" class="btn btn-outline-danger mt-auto" data-bs-toggle="modal" data-bs-target="#viderPretsModal"
                                    @disabled($compteurs['prets'] < 1)>
                                    Vider les prêts
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                <div class="text-muted small">Banques</div>
                                <div class="fs-4 fw-semibold mb-3">{{ number_format($compteurs['banques'], 0, ',', ' ') }}</div>
                                <button type="button" class="btn btn-outline-danger mt-auto" data-bs-toggle="modal" data-bs-target="#viderBanquesModal"
                                    @disabled($compteurs['banques'] < 1)>
                                    Vider les banques
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column">
                                <div class="text-muted small">Caisse</div>
                                <div class="fs-4 fw-semibold">{{ number_format($compteurs['solde_caisse'], 0, ',', ' ') }} FCFA</div>
                                <div class="small text-muted mb-3">{{ number_format($compteurs['mouvements_caisse'], 0, ',', ' ') }} mouvement(s)</div>
                                <button type="button" class="btn btn-outline-danger mt-auto" data-bs-toggle="modal" data-bs-target="#viderCaisseModal"
                                    @disabled($compteurs['mouvements_caisse'] < 1 && abs($compteurs['solde_caisse']) < 0.01 && abs($compteurs['utilisable_caisse']) < 0.01)>
                                    Vider la caisse
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="solderBordereauxModal" tabindex="-1" aria-labelledby="solderBordereauxModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('parametres.bordereaux.solder') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="solderBordereauxModalLabel">Solder tous les bordereaux</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            {{ number_format($synthese['a_solder'], 0, ',', ' ') }} bordereau(x) vont passer au statut <strong>soldé</strong>, pour {{ number_format($synthese['reste'], 0, ',', ' ') }} FCFA.
                        </p>
                        <p class="mb-0 text-muted">Les tickets de ces bordereaux seront soldés. La caisse reste inchangée.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Solder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viderFinancementsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('parametres.financements.vider') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Vider les financements</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        Les {{ number_format($compteurs['financements'], 0, ',', ' ') }} financement(s) seront supprimés. Les soldes financement des agents repassent à zéro.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Vider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viderPretsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('parametres.prets.vider') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Vider les prêts</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        Les {{ number_format($compteurs['prets'], 0, ',', ' ') }} prêt(s) agents et leurs remboursements seront supprimés.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Vider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viderBanquesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('parametres.banques.vider') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Vider les banques</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        Les {{ number_format($compteurs['banques'], 0, ',', ' ') }} banque(s) et leurs mouvements seront supprimés.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Vider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viderCaisseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('parametres.caisse.vider') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Vider la caisse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        Les {{ number_format($compteurs['mouvements_caisse'], 0, ',', ' ') }} mouvement(s) seront supprimés. Le solde et le montant utilisable repassent à 0.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Vider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
