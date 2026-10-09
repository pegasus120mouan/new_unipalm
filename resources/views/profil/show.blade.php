@extends('layout.main')

@section('title', 'Mon profil')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="profil-page-title mb-0">Mon profil</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mon profil</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @php
        $activeTab = session('active_tab') ?? ($errors->hasAny(['old_password', 'new_password']) ? 'password' : 'infos');
    @endphp

    <section class="row g-4">
        <div class="col-lg-4">
            <div class="card profil-card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        @if ($utilisateur->hasAvatarImage())
                            <img src="{{ $utilisateur->avatar_url }}" alt="{{ $utilisateur->full_name }}"
                                id="profilAvatarPreview" class="profil-avatar rounded-circle">
                        @else
                            <div id="profilAvatarFallback" class="profil-avatar profil-avatar-fallback rounded-circle mx-auto">
                                {{ strtoupper(mb_substr($utilisateur->formatted_nom ?: $utilisateur->login, 0, 1, 'UTF-8')) }}
                            </div>
                            <img src="" alt="" id="profilAvatarPreview" class="profil-avatar rounded-circle d-none">
                        @endif
                    </div>

                    <h5 class="fw-bold mb-1">{{ $utilisateur->full_name }}</h5>
                    <p class="text-success fw-semibold mb-4">
                        <i class="bi bi-person-badge"></i> {{ $utilisateur->role_label }}
                    </p>

                    <form method="POST" action="{{ route('profil.photo.update') }}" enctype="multipart/form-data"
                        class="profil-upload-box text-start">
                        @csrf
                        <label for="profilPhotoInput" class="form-label fw-semibold small mb-2">
                            <i class="bi bi-camera"></i> Changer ma photo
                        </label>
                        <input type="file" name="photo" id="profilPhotoInput"
                            class="form-control form-control-sm mb-2 @error('photo') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/gif,image/webp" required>
                        @error('photo')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-success btn-sm w-100">
                            <i class="bi bi-upload"></i> Mettre à jour la photo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card profil-card h-100">
                <div class="card-body">
                    <ul class="nav nav-pills profil-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'infos' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#profil-infos" type="button" role="tab">
                                <i class="bi bi-person-lines-fill"></i> Mes informations
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'password' ? 'active' : '' }}" data-bs-toggle="pill"
                                data-bs-target="#profil-password" type="button" role="tab">
                                <i class="bi bi-key"></i> Changer mon mot de passe
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade {{ $activeTab === 'infos' ? 'show active' : '' }}" id="profil-infos" role="tabpanel">
                            <ul class="list-group list-group-flush profil-info-list">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-person me-1"></i> Nom</span>
                                    <span class="fw-semibold">{{ $utilisateur->formatted_nom ?: '—' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-person-badge me-1"></i> Prénoms</span>
                                    <span class="fw-semibold">{{ $utilisateur->formatted_prenoms ?: '—' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-telephone me-1"></i> Contact</span>
                                    <span class="fw-semibold">{{ $utilisateur->contact ?: '—' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-at me-1"></i> Login</span>
                                    <code>{{ $utilisateur->login }}</code>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-shield-check me-1"></i> Rôle</span>
                                    <span class="fw-semibold">{{ $utilisateur->role_label }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-toggle-on me-1"></i> Statut du compte</span>
                                    @if ($utilisateur->isActive())
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted"><i class="bi bi-calendar3 me-1"></i> Compte créé le</span>
                                    <span class="fw-semibold">{{ $utilisateur->created_at?->format('d/m/Y') ?? '—' }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-pane fade {{ $activeTab === 'password' ? 'show active' : '' }}" id="profil-password" role="tabpanel">
                            <form method="POST" action="{{ route('profil.password.update') }}" class="profil-password-form">
                                @csrf
                                <div class="mb-3">
                                    <label for="old_password" class="form-label">Mot de passe actuel</label>
                                    <div class="input-group">
                                        <input type="password" name="old_password" id="old_password"
                                            class="form-control @error('old_password') is-invalid @enderror"
                                            autocomplete="current-password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="old_password" title="Afficher">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @error('old_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="new_password"
                                            class="form-control @error('new_password') is-invalid @enderror"
                                            autocomplete="new-password" minlength="4" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password" title="Afficher">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="form-text">4 caractères minimum.</div>
                                </div>
                                <div class="mb-4">
                                    <label for="new_password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                            class="form-control" autocomplete="new-password" minlength="4" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password_confirmation" title="Afficher">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="bi bi-key"></i> Changer mon mot de passe
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <style>
        .profil-page-title {
            color: #2e8b57;
            font-weight: 700;
        }

        .profil-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .profil-avatar {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border: 4px solid #e9f7ef;
            box-shadow: 0 8px 20px rgba(46, 139, 87, 0.15);
        }

        .profil-avatar-fallback {
            display: flex;
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

        .profil-password-form {
            max-width: 520px;
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
                    preview.src = e.target?.result || '';
                    preview.classList.remove('d-none');
                    fallback?.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });

            document.querySelectorAll('.toggle-password').forEach(function (button) {
                button.addEventListener('click', function () {
                    const field = document.getElementById(button.dataset.target);
                    const icon = button.querySelector('i');
                    if (!field) {
                        return;
                    }
                    const show = field.type === 'password';
                    field.type = show ? 'text' : 'password';
                    icon?.classList.toggle('bi-eye', !show);
                    icon?.classList.toggle('bi-eye-slash', show);
                });
            });
        });
    </script>
@endpush
