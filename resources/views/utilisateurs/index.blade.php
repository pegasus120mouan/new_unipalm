@extends('layout.main')

@section('title', 'Liste des utilisateurs')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Liste des utilisateurs</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
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

    @if ($errors->has('statut'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('statut') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    <section class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #435ebe;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['total'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Total utilisateurs</div>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #198754;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['actifs'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Comptes actifs</div>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 text-white h-100" style="background-color: #dc3545;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">{{ number_format($stats['inactifs'], 0, '', ' ') }}</div>
                            <div class="small opacity-75">Comptes inactifs</div>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUtilisateurModal">
                            <i class="bi bi-person-plus-fill"></i> Enregistrer un utilisateur
                        </button>
                        <a href="#utilisateur-filters" class="btn btn-info">
                            <i class="bi bi-search"></i> Rechercher
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row mb-4" id="utilisateur-filters">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Filtres de recherche
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('utilisateurs.index') }}">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <label for="search_login" class="form-label">Login</label>
                                <input type="text" name="search_login" id="search_login" class="form-control"
                                    placeholder="Rechercher par login..." value="{{ $filters['search_login'] }}">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="search_nom" class="form-label">Nom / Prénoms</label>
                                <input type="text" name="search_nom" id="search_nom" class="form-control"
                                    placeholder="Rechercher par nom..." value="{{ $filters['search_nom'] }}">
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="search_role" class="form-label">Rôle</label>
                                <select name="search_role" id="search_role" class="form-select">
                                    <option value="">Tous les rôles</option>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['search_role'] === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <label for="statut" class="form-label">Statut</label>
                                <select name="statut" id="statut" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="actif" @selected($filters['statut'] === 'actif')>Actif</option>
                                    <option value="inactif" @selected($filters['statut'] === 'inactif')>Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                            <a href="{{ route('utilisateurs.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Réinitialiser
                            </a>
                        </div>
                    </form>

                    @if ($hasFilters)
                        <div class="alert alert-light border mt-3 mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>{{ $utilisateurs->total() }}</strong> utilisateur(s) trouvé(s)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-semibold"><i class="bi bi-people"></i> Utilisateurs</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                        <small class="text-muted"><i class="bi bi-pencil-square"></i> Cliquez sur Nom, Prénoms, Contact ou Login puis Entrée pour enregistrer</small>
                        <span class="badge bg-dark">{{ $utilisateurs->total() }} utilisateur(s)</span>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover align-middle mb-0 utilisateurs-data-table">
                        <thead class="utilisateurs-table-header">
                            <tr>
                                <th class="text-center" style="width: 4rem;">Photo</th>
                                <th>Nom</th>
                                <th>Prénoms</th>
                                <th>Contact</th>
                                <th>Login</th>
                                <th>Rôle</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($utilisateurs as $utilisateur)
                                @php
                                    $roleBadge = match ($utilisateur->role) {
                                        'admin' => 'bg-danger',
                                        'directeur' => 'bg-primary',
                                        'validateur' => 'bg-info',
                                        'caissiere' => 'bg-success',
                                        'operateur' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr data-utilisateur-row="{{ $utilisateur->id }}">
                                    <td class="text-center">
                                        <a href="{{ route('utilisateurs.show', $utilisateur) }}"
                                            class="utilisateur-photo-link"
                                            title="Profil de {{ $utilisateur->full_name }}">
                                            <div class="utilisateur-photo">
                                                @if ($utilisateur->hasAvatarImage())
                                                    <img src="{{ $utilisateur->avatar_url }}"
                                                        alt="{{ $utilisateur->formatted_nom }}"
                                                        class="utilisateur-photo__img rounded-circle"
                                                        width="40" height="40"
                                                        onerror="this.classList.add('d-none'); this.nextElementSibling.classList.remove('d-none');">
                                                    <div class="utilisateur-photo__fallback d-none rounded-circle">
                                                        {{ strtoupper(mb_substr($utilisateur->formatted_nom, 0, 1, 'UTF-8')) }}
                                                    </div>
                                                @else
                                                    <div class="utilisateur-photo__fallback rounded-circle">
                                                        {{ strtoupper(mb_substr($utilisateur->formatted_nom, 0, 1, 'UTF-8')) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td class="utilisateur-editable-cell fw-semibold" tabindex="0"
                                        data-field="nom"
                                        data-url="{{ route('utilisateurs.inline-update', $utilisateur) }}"
                                        data-value="{{ $utilisateur->nom }}">
                                        <span class="utilisateur-editable-display">{{ $utilisateur->formatted_nom }}</span>
                                    </td>
                                    <td class="utilisateur-editable-cell" tabindex="0"
                                        data-field="prenoms"
                                        data-url="{{ route('utilisateurs.inline-update', $utilisateur) }}"
                                        data-value="{{ $utilisateur->prenoms }}">
                                        <span class="utilisateur-editable-display">{{ $utilisateur->formatted_prenoms }}</span>
                                    </td>
                                    <td class="utilisateur-editable-cell" tabindex="0"
                                        data-field="contact"
                                        data-url="{{ route('utilisateurs.inline-update', $utilisateur) }}"
                                        data-value="{{ $utilisateur->contact }}">
                                        <span class="utilisateur-editable-display">
                                            @if ($utilisateur->contact)
                                                <span class="badge bg-light text-dark border">{{ $utilisateur->contact }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </span>
                                    </td>
                                    <td class="utilisateur-editable-cell utilisateur-editable-cell--login" tabindex="0"
                                        data-field="login"
                                        data-url="{{ route('utilisateurs.inline-update', $utilisateur) }}"
                                        data-value="{{ $utilisateur->login }}">
                                        <span class="utilisateur-editable-display utilisateur-login-text">{{ $utilisateur->login }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $roleBadge }}">{{ $utilisateur->role_label }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($utilisateur->isActive())
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($utilisateur->id !== auth()->id())
                                            <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-center">
                                                <form method="POST" action="{{ route('utilisateurs.toggle-statut', $utilisateur) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $utilisateur->isActive() ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                        title="{{ $utilisateur->isActive() ? 'Désactiver' : 'Activer' }}">
                                                        <i class="bi bi-{{ $utilisateur->isActive() ? 'toggle-off' : 'toggle-on' }}"></i>
                                                        {{ $utilisateur->isActive() ? 'Désactiver' : 'Activer' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('utilisateurs.destroy', $utilisateur) }}" class="d-inline form-delete-utilisateur">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer"
                                                        data-user-name="{{ $utilisateur->full_name }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted small">Votre compte</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Aucun utilisateur trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($utilisateurs->hasPages())
                    <div class="card-footer">
                        {{ $utilisateurs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade" id="addUtilisateurModal" tabindex="-1" aria-labelledby="addUtilisateurModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUtilisateurModalLabel">
                        <i class="bi bi-person-plus-fill"></i> Enregistrer un utilisateur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('utilisateurs.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
                                    value="{{ old('nom') }}" required>
                                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="prenoms" class="form-label">Prénoms</label>
                                <input type="text" name="prenoms" id="prenoms" class="form-control @error('prenoms') is-invalid @enderror"
                                    value="{{ old('prenoms') }}" required>
                                @error('prenoms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="contact" class="form-label">Contact</label>
                                <input type="text" name="contact" id="contact" class="form-control @error('contact') is-invalid @enderror"
                                    value="{{ old('contact') }}" required>
                                @error('contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="login" class="form-label">Login</label>
                                <input type="text" name="login" id="login" class="form-control @error('login') is-invalid @enderror"
                                    value="{{ old('login') }}" required>
                                @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmation</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="role" class="form-label">Rôle</label>
                                <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="">Sélectionner un rôle</option>
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="utilisateurInlineSuccessModal" tabindex="-1" aria-labelledby="utilisateurInlineSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4 px-4">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 56px; height: 56px;">
                        <i class="bi bi-check-lg fs-2"></i>
                    </div>
                    <h5 class="mb-2" id="utilisateurInlineSuccessModalLabel">Modification effectuée</h5>
                    <p class="text-muted mb-0 small" id="utilisateurInlineSuccessMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .utilisateurs-table-header {
            background: #111;
        }

        .utilisateurs-table-header th {
            color: #fff !important;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: none;
            padding: 0.95rem 1rem;
            white-space: nowrap;
        }

        .utilisateurs-data-table tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }

        .utilisateur-photo-link {
            display: inline-flex;
            text-decoration: none;
            transition: transform 0.15s ease;
        }

        .utilisateur-photo-link:hover {
            transform: scale(1.05);
        }

        .utilisateur-photo-link:hover .utilisateur-photo__img,
        .utilisateur-photo-link:hover .utilisateur-photo__fallback {
            border-color: #435ebe;
            box-shadow: 0 0 0 2px rgba(67, 94, 190, 0.2);
        }

        .utilisateur-photo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .utilisateur-photo__img {
            object-fit: cover;
            border: 2px solid #e9ecef;
        }

        .utilisateur-photo__fallback {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #435ebe;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            border: 2px solid #e9ecef;
        }

        .utilisateur-editable-cell {
            cursor: text;
            min-width: 7rem;
        }

        .utilisateur-editable-cell:hover:not(.is-editing) {
            background-color: rgba(67, 94, 190, 0.06);
            outline: 1px dashed rgba(67, 94, 190, 0.25);
        }

        .utilisateur-editable-cell.is-editing {
            padding: 0.25rem;
        }

        .utilisateur-editable-cell.is-saving {
            opacity: 0.6;
        }

        .utilisateur-editable-input {
            width: 100%;
            min-width: 6rem;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = @json(csrf_token());
            const fieldLabels = { nom: 'Nom', prenoms: 'Prénoms', contact: 'Contact', login: 'Login' };
            const successModalEl = document.getElementById('utilisateurInlineSuccessModal');
            const successModal = successModalEl && typeof bootstrap !== 'undefined'
                ? (bootstrap.Modal.getInstance(successModalEl) || new bootstrap.Modal(successModalEl))
                : null;

            function escapeHtml(text) {
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function displayValue(field, value) {
                if (!value && field === 'contact') {
                    return '<span class="text-muted">—</span>';
                }

                if (field === 'contact') {
                    return `<span class="badge bg-light text-dark border">${escapeHtml(value)}</span>`;
                }

                if (field === 'login') {
                    return `<code>${escapeHtml(value)}</code>`;
                }

                return escapeHtml(value);
            }

            function showSuccessModal(field, display) {
                const label = fieldLabels[field] || field;
                const messageEl = document.getElementById('utilisateurInlineSuccessMessage');

                if (messageEl) {
                    messageEl.textContent = `${label} enregistré : ${display}`;
                }

                successModal?.show();
            }

            function finishEdit(cell, value, field, display) {
                cell.classList.remove('is-editing', 'is-saving');
                cell.dataset.value = value;
                cell.innerHTML = `<span class="utilisateur-editable-display">${displayValue(field, display ?? value)}</span>`;
            }

            function cancelEdit(cell) {
                const field = cell.dataset.field;
                const value = cell.dataset.value || '';
                let display = value;

                if (field === 'nom' || field === 'prenoms') {
                    display = cell.querySelector('.utilisateur-editable-display')?.textContent?.trim() || value;
                }

                finishEdit(cell, value, field, display);
            }

            async function saveEdit(cell, input) {
                const field = cell.dataset.field;
                const url = cell.dataset.url;
                const original = (cell.dataset.value || '').trim();
                const value = input.value.trim();

                if (value === '') {
                    alert('Ce champ ne peut pas être vide.');
                    input.focus();
                    return;
                }

                if (value === original) {
                    cancelEdit(cell);
                    return;
                }

                cell.classList.add('is-saving');

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ field, value }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const message = data.message
                            || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Erreur lors de la mise à jour.');
                        throw new Error(message);
                    }

                    const display = data.display ?? data.value;
                    finishEdit(cell, data.value, field, display);
                    showSuccessModal(field, display);

                    const row = cell.closest('tr');
                    const deleteBtn = row?.querySelector('.form-delete-utilisateur button[type="submit"]');
                    if (deleteBtn && (field === 'nom' || field === 'prenoms')) {
                        const nomCell = row.querySelector('[data-field="nom"]');
                        const prenomsCell = row.querySelector('[data-field="prenoms"]');
                        const nom = nomCell?.dataset.value || '';
                        const prenoms = prenomsCell?.dataset.value || '';
                        deleteBtn.dataset.userName = `${nom} ${prenoms}`.trim();
                    }
                } catch (error) {
                    cell.classList.remove('is-saving');
                    alert(error.message || 'Impossible d\'enregistrer la modification.');
                    input.focus();
                }
            }

            function startEdit(cell) {
                if (cell.classList.contains('is-editing')) {
                    return;
                }

                document.querySelectorAll('.utilisateur-editable-cell.is-editing').forEach(cancelEdit);

                const value = cell.dataset.value || '';

                cell.classList.add('is-editing');
                cell.innerHTML = `<input type="text" class="form-control form-control-sm utilisateur-editable-input" value="${escapeHtml(value)}">`;

                const input = cell.querySelector('.utilisateur-editable-input');
                input.focus();
                input.select();

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        saveEdit(cell, input);
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        cancelEdit(cell);
                    }
                });

                input.addEventListener('blur', function () {
                    if (cell.classList.contains('is-editing') && !cell.classList.contains('is-saving')) {
                        cancelEdit(cell);
                    }
                });
            }

            document.querySelectorAll('.utilisateur-editable-cell').forEach(function (cell) {
                cell.addEventListener('click', function () {
                    startEdit(cell);
                });

                cell.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        startEdit(cell);
                    }
                });
            });

            document.querySelectorAll('.form-delete-utilisateur').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    const button = form.querySelector('button[type="submit"]');
                    const name = button?.dataset.userName || 'cet utilisateur';

                    if (!confirm(`Confirmer la suppression de ${name} ?`)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>

    @if ($errors->has('nom') || $errors->has('prenoms') || $errors->has('contact') || $errors->has('login') || $errors->has('password') || $errors->has('role'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('addUtilisateurModal')).show();
            });
        </script>
    @endif
@endpush
