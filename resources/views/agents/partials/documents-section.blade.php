@php
    use App\Models\AgentDocument;
    use App\Services\AgentDocumentService;

    $documentService = app(AgentDocumentService::class);
    $documentTypes = AgentDocument::types();
@endphp

<section class="row mb-4" id="agent-documents">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-folder2-open"></i> Mes documents</span>
                <span class="text-muted small">Vérification agent (CNI, photo, contrat)</span>
            </div>
            <div class="card-body">
                @if (! $minioConfigured)
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        Le stockage MinIO n'est pas configuré. Les documents ne peuvent pas être enregistrés pour le moment.
                    </div>
                @else
                    <div class="row g-3">
                        @foreach ($documentTypes as $type => $label)
                            @php
                                $document = $documents->get($type);
                                $viewUrl = $documentService->viewUrl($document);
                                $errorKey = 'document_'.$type;
                            @endphp
                            <div class="col-md-6 col-xl-3">
                                <div class="border rounded h-100 p-3 d-flex flex-column">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div>
                                            <h6 class="mb-1">{{ $label }}</h6>
                                            @if ($document)
                                                <span class="badge bg-success">Déposé</span>
                                            @else
                                                <span class="badge bg-secondary">Manquant</span>
                                            @endif
                                        </div>
                                        @if ($type === AgentDocument::TYPE_CNI_RECTO || $type === AgentDocument::TYPE_CNI_VERSO)
                                            <i class="bi bi-credit-card-2-front text-primary fs-4"></i>
                                        @elseif ($type === AgentDocument::TYPE_PHOTO_IDENTITE)
                                            <i class="bi bi-person-bounding-box text-info fs-4"></i>
                                        @else
                                            <i class="bi bi-file-earmark-text text-warning fs-4"></i>
                                        @endif
                                    </div>

                                    @if ($document && $viewUrl)
                                        <div class="mb-3 flex-grow-1">
                                            @if ($document->isImage())
                                                <a href="{{ $viewUrl }}" target="_blank" class="d-block">
                                                    <img src="{{ $viewUrl }}" alt="{{ $label }}"
                                                        class="img-fluid rounded border"
                                                        style="max-height: 140px; object-fit: cover; width: 100%;">
                                                </a>
                                            @elseif ($document->isPdf())
                                                <a href="{{ $viewUrl }}" target="_blank" class="btn btn-outline-danger btn-sm w-100">
                                                    <i class="bi bi-file-earmark-pdf"></i> Voir le PDF
                                                </a>
                                            @else
                                                <a href="{{ $viewUrl }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="bi bi-eye"></i> Voir le document
                                                </a>
                                            @endif
                                            <div class="small text-muted mt-2">
                                                {{ $document->original_name ?? 'Fichier' }}
                                                <br>
                                                {{ $document->updated_at?->format('d/m/Y H:i') ?? $document->created_at?->format('d/m/Y H:i') }}
                                                @if ($document->uploader)
                                                    · {{ $document->uploader->full_name }}
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-muted small flex-grow-1 mb-3">
                                            Aucun fichier déposé pour ce type de document.
                                        </p>
                                    @endif

                                    @if ($errors->has($errorKey))
                                        <div class="alert alert-danger py-2 small mb-2">{{ $errors->first($errorKey) }}</div>
                                    @endif

                                    <form method="POST"
                                        action="{{ route('agents.documents.store', ['agent' => $agent, 'type' => $type]) }}"
                                        enctype="multipart/form-data"
                                        class="mt-auto">
                                        @csrf
                                        <div class="mb-2">
                                            <input type="file"
                                                name="document"
                                                id="document_{{ $type }}"
                                                class="form-control form-control-sm @error($errorKey) is-invalid @enderror"
                                                accept="{{ $type === AgentDocument::TYPE_CONTRAT ? '.jpg,.jpeg,.png,.pdf' : '.jpg,.jpeg,.png,.pdf' }}"
                                                {{ $document ? '' : 'required' }}>
                                            <div class="form-text">
                                                JPG, PNG ou PDF
                                                @if ($type === AgentDocument::TYPE_CONTRAT)
                                                    (max. 15 Mo)
                                                @else
                                                    (max. 10 Mo)
                                                @endif
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="bi bi-upload"></i>
                                            {{ $document ? 'Remplacer' : 'Téléverser' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
