@extends('layout.main')

@section('title', 'Accès — '.$roles[$role])

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Accès : {{ $roles[$role] }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('role-permissions.index') }}">Rôles</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $roles[$role] }}</li>
            </ol>
        </nav>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('role-permissions.update', $role) }}">
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="text-muted mb-0">
                Cochez les modules accessibles pour le profil <strong>{{ $roles[$role] }}</strong>.
            </p>
            <div class="d-flex gap-2">
                <a href="{{ route('role-permissions.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Enregistrer
                </button>
            </div>
        </div>

        <div class="row">
            @foreach ($groups as $groupKey => $group)
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ $group['label'] }}</h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary select-group-all" data-group="{{ $groupKey }}">
                                Tout sélectionner
                            </button>
                        </div>
                        <div class="card-body">
                            @foreach ($group['modules'] as $moduleKey => $moduleLabel)
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input module-checkbox"
                                        type="checkbox"
                                        name="modules[]"
                                        value="{{ $moduleKey }}"
                                        id="module-{{ $groupKey }}-{{ $moduleKey }}"
                                        data-group="{{ $groupKey }}"
                                        @checked(in_array($moduleKey, $assigned, true))
                                    >
                                    <label class="form-check-label" for="module-{{ $groupKey }}-{{ $moduleKey }}">
                                        {{ $moduleLabel }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('role-permissions.index') }}" class="btn btn-light">Annuler</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Enregistrer les accès
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.select-group-all').forEach(function (button) {
            button.addEventListener('click', function () {
                const group = button.dataset.group;
                const checkboxes = document.querySelectorAll('.module-checkbox[data-group="' + group + '"]');
                const allChecked = Array.from(checkboxes).every(function (cb) { return cb.checked; });

                checkboxes.forEach(function (cb) {
                    cb.checked = !allChecked;
                });

                button.textContent = allChecked ? 'Tout sélectionner' : 'Tout désélectionner';
            });
        });
    </script>
@endpush
