@extends('layout.main')

@section('title', 'Gestion des rôles')

@section('page-heading')
    <div class="page-heading d-flex justify-content-between align-items-center">
        <h3>Gestion des rôles</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Accueil</a></li>
                <li class="breadcrumb-item active" aria-current="page">Rôles et accès</li>
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
        @foreach ($roles as $roleKey => $roleLabel)
            @php
                $assigned = $roleModules[$roleKey] ?? [];
                $totalModules = collect($groups)->sum(fn ($group) => count($group['modules']));
            @endphp
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1">{{ $roleLabel }}</h5>
                                <span class="text-muted small">{{ $roleKey }}</span>
                            </div>
                            <span class="badge bg-primary">{{ count($assigned) }} / {{ $totalModules }}</span>
                        </div>

                        <p class="text-muted small flex-grow-1">
                            @if (count($assigned) === 0)
                                Aucun module attribué.
                            @else
                                Modules :
                                {{ collect($groups)->flatMap(fn ($group) => $group['modules'])->only($assigned)->take(4)->implode(', ') }}
                                @if (count($assigned) > 4)
                                    …
                                @endif
                            @endif
                        </p>

                        <a href="{{ route('role-permissions.edit', $roleKey) }}" class="btn btn-outline-primary mt-2">
                            <i class="bi bi-sliders"></i> Configurer les accès
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
@endsection
