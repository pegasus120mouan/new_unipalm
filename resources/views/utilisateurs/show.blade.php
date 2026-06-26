@extends('layout.main')

@section('title')
    Profil — {{ $utilisateur->full_name }}
@endsection

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="profil-page-title mb-1">Profil de {{ $utilisateur->full_name }}</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('utilisateurs.index') }}"><i class="bi bi-people"></i> Utilisateurs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $utilisateur->full_name }}</li>
                </ol>
            </nav>
        </div>
        @if ($utilisateur->isActive())
            <span class="badge bg-success px-3 py-2"><i class="bi bi-person-check"></i> Profil actif</span>
        @else
            <span class="badge bg-secondary px-3 py-2"><i class="bi bi-person-x"></i> Profil inactif</span>
        @endif
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @php
        $roleBadge = match ($utilisateur->role) {
            'admin' => 'bg-danger',
            'directeur' => 'bg-primary',
            'validateur' => 'bg-info',
            'caissiere' => 'bg-success',
            'operateur' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
        $activeTab = session('active_tab', 'profile');
        $defaultPassword = \App\Models\Utilisateur::DEFAULT_PASSWORD;
    @endphp

    <section class="row g-4">
        <div class="col-lg-4">
            <div class="card profil-sidebar-card h-100">
                <div class="card-body text-center">
                    <div class="profil-avatar-wrap mx-auto mb-3">
                        @if ($utilisateur->hasAvatarImage())
                            <img src="{{ $utilisateur->avatar_url }}"
                                alt="{{ $utilisateur->formatted_nom }}"
                                id="profilAvatarPreview"
                                class="profil-avatar rounded-circle">
                        @else
                            <div id="profilAvatarFallback" class="profil-avatar profil-avatar-fallback rounded-circle">
                                {{ strtoupper(mb_substr($utilisateur->formatted_nom, 0, 1, 'UTF-8')) }}
                            </div>
                            <img src="" alt="" id="profilAvatarPreview" class="profil-avatar rounded-circle d-none">
                        @endif
                    </div>

                    <h5 class="fw-bold mb-1">{{ $utilisateur->formatted_nom }} {{ $utilisateur->formatted_prenoms }}</h5>
                    <p class="text-success fw-semibold mb-4">
                        <i class="bi bi-person-badge"></i> {{ $utilisateur->role_label }}
                    </p>

                    <div class="profil-upload-box text-start mb-4">
                        <form method="POST" action="{{ route('utilisateurs.photo.store', $utilisateur) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="text-center mb-3">
                                <i class="bi bi-cloud-upload fs-2 text-success"></i>
                                <div class="fw-semibold">Changer la photo de profil</div>
                            </div>
                            <input type="file" name="photo" id="profilPhotoInput"
                                class="form-control form-control-sm mb-3 @error('photo') is-invalid @enderror"
                                accept="image/jpeg,image/png,image/gif,image/webp" required>
                            @error('photo')
                                <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-upload"></i> Mettre à jour
                            </button>
                        </form>
                    </div>

                    <ul class="list-group list-group-flush profil-info-list text-start">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-person me-1"></i> Nom</span>
                            <span class="fw-semibold text-success">{{ $utilisateur->formatted_nom }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-person-badge me-1"></i> Prénoms</span>
                            <span class="fw-semibold text-success">{{ $utilisateur->formatted_prenoms }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-telephone me-1"></i> Contact</span>
                            <span class="fw-semibold text-success">{{ $utilisateur->contact }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted"><i class="bi bi-at me-1"></i> Login</span>
                            <span class="fw-semibold text-success"><code>{{ $utilisateur->login }}</code></span>
                        </li>
                    </ul>

                    <a href="{{ route('utilisateurs.index') }}" class="btn btn-outline-secondary w-100 mt-4">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card profil-main-card h-100">
                <div class="card-body">
                    <ul class="nav nav-pills profil-tabs mb-4" id="profilTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}" id="profile-tab"
                                data-bs-toggle="pill" data-bs-target="#profile-panel" type="button" role="tab">
                                <i class="bi bi-person-lines-fill"></i> Modifier mon profil
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}" id="password-tab"
                                data-bs-toggle="pill" data-bs-target="#password-panel" type="button" role="tab">
                                <i class="bi bi-key"></i> Changer mot de passe
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="profilTabsContent">
                        <div class="tab-pane fade {{ $activeTab === 'profile' ? 'show active' : '' }}" id="profile-panel" role="tabpanel">
                            <h5 class="mb-4"><i class="bi bi-pencil-square text-primary"></i> Modifier les informations personnelles</h5>

                            <form method="POST" action="{{ route('utilisateurs.update', $utilisateur) }}">
                                @csrf
                                @method('PUT')
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                            value="{{ old('nom', $utilisateur->nom) }}" required>
                                        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenoms" class="form-label">Prénoms</label>
                                        <input type="text" name="prenoms" id="prenoms" class="form-control @error('prenoms') is-invalid @enderror"
                                            value="{{ old('prenoms', $utilisateur->prenoms) }}" required>
                                        @error('prenoms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="contact" class="form-label">Contact</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="text" name="contact" id="contact" class="form-control @error('contact') is-invalid @enderror"
                                                value="{{ old('contact', $utilisateur->contact) }}" required>
                                            @error('contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
                                    </button>
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="bi bi-save"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'password' ? 'show active' : '' }}" id="password-panel" role="tabpanel">
                            <h5 class="mb-3"><i class="bi bi-shield-lock text-danger"></i> Changer le mot de passe</h5>
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle"></i> Le mot de passe est chiffré en SHA-256 comme dans l'application legacy.
                            </div>

                            <form method="POST" action="{{ route('utilisateurs.password.update', $utilisateur) }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="old_password" class="form-label">Ancien mot de passe</label>
                                    <input type="password" name="old_password" id="old_password"
                                        class="form-control @error('old_password') is-invalid @enderror" required>
                                    @error('old_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="new_password" id="new_password"
                                        class="form-control @error('new_password') is-invalid @enderror" required>
                                    @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="new_password_confirmation" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="bi bi-key"></i> Mettre à jour le mot de passe
                                    </button>
                                </div>
                            </form>

                            <hr class="my-4">

                            <div class="border rounded p-3 bg-light">
                                <h6 class="mb-2"><i class="bi bi-arrow-repeat text-warning"></i> Mot de passe oublié ?</h6>
                                <p class="text-muted small mb-3">
                                    Si vous ne connaissez plus votre ancien mot de passe, vous pouvez le réinitialiser
                                    au mot de passe par défaut <code>{{ $defaultPassword }}</code>.
                                </p>
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                    <i class="bi bi-arrow-clockwise"></i> Régénérer le mot de passe par défaut
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="resetPasswordModalLabel">
                        Réinitialiser le mot de passe
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="text-center mb-4">
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 64px; height: 64px;">
                            <i class="bi bi-shield-lock fs-2"></i>
                        </div>
                        <p class="text-muted mb-0">
                            Cette action remplacera le mot de passe actuel de
                            <strong>{{ $utilisateur->full_name }}</strong>.
                        </p>
                    </div>

                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="small text-muted mb-1">Nouveau mot de passe par défaut</div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <code class="fs-6 text-dark user-select-all" id="resetPasswordValue">{{ $defaultPassword }}</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="copyDefaultPasswordBtn" title="Copier">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-warning mb-0 d-flex align-items-start gap-2 small">
                        <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                        <span>
                            L'utilisateur devra se connecter avec ce mot de passe, puis pourra le modifier depuis cet onglet.
                        </span>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Annuler
                    </button>
                    <form method="POST" action="{{ route('utilisateurs.password.reset', $utilisateur) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-arrow-clockwise"></i> Confirmer la réinitialisation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .profil-page-title {
            color: #2e8b57;
            font-weight: 700;
        }

        .profil-sidebar-card,
        .profil-main-card {
            border: 0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            border-radius: 1rem;
        }

        .profil-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #e9f7ef;
            box-shadow: 0 8px 20px rgba(46, 139, 87, 0.15);
        }

        .profil-avatar-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #2e8b57;
            color: #fff;
            font-size: 3rem;
            font-weight: 700;
        }

        .profil-upload-box {
            border: 2px dashed #c3e6cb;
            border-radius: 0.75rem;
            padding: 1rem;
            background: #f8fff9;
        }

        .profil-info-list .list-group-item {
            border-color: #f0f0f0;
        }

        .profil-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
            border-radius: 0.5rem;
            margin-right: 0.5rem;
        }

        .profil-tabs .nav-link.active {
            background: #2e8b57;
            color: #fff;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('profilPhotoInput');
            const preview = document.getElementById('profilAvatarPreview');
            const fallback = document.getElementById('profilAvatarFallback');

            input?.addEventListener('change', function () {
                const file = input.files?.[0];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    if (preview) {
                        preview.src = e.target?.result || '';
                        preview.classList.remove('d-none');
                    }
                    fallback?.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });

            const copyBtn = document.getElementById('copyDefaultPasswordBtn');
            const passwordValue = document.getElementById('resetPasswordValue');

            copyBtn?.addEventListener('click', function () {
                const text = passwordValue?.textContent?.trim();
                if (!text || !navigator.clipboard) {
                    return;
                }

                navigator.clipboard.writeText(text).then(function () {
                    const icon = copyBtn.querySelector('i');
                    copyBtn.classList.replace('btn-outline-secondary', 'btn-success');
                    icon?.classList.replace('bi-clipboard', 'bi-check-lg');

                    setTimeout(function () {
                        copyBtn.classList.replace('btn-success', 'btn-outline-secondary');
                        icon?.classList.replace('bi-check-lg', 'bi-clipboard');
                    }, 2000);
                });
            });
        });
    </script>
@endpush
