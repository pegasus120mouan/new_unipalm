<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Models\Utilisateur;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AgentDocumentService
{
    public function __construct(
        private readonly MinioStorageService $minio,
    ) {}

    /**
     * @return Collection<string, AgentDocument>
     */
    public function documentsByType(Agent $agent): Collection
    {
        return AgentDocument::query()
            ->with('uploader')
            ->where('id_agent', $agent->id_agent)
            ->get()
            ->keyBy('type');
    }

    public function viewUrl(?AgentDocument $document): ?string
    {
        if ($document === null || $document->object_key === '') {
            return null;
        }

        return route('agents.documents.show', [
            'agent' => $document->id_agent,
            'type' => $document->type,
        ]);
    }

    public function upload(Agent $agent, string $type, UploadedFile $file, Utilisateur $user): AgentDocument
    {
        if (! array_key_exists($type, AgentDocument::types())) {
            throw new InvalidArgumentException('Type de document invalide.');
        }

        if (! $this->minio->isConfigured()) {
            throw new RuntimeException('MinIO n\'est pas configuré. Impossible d\'enregistrer le document.');
        }

        return DB::transaction(function () use ($agent, $type, $file, $user): AgentDocument {
            $objectKey = $this->minio->uploadAgentDocument($file, (int) $agent->id_agent, $type);

            /** @var AgentDocument $document */
            $document = AgentDocument::query()->updateOrCreate(
                [
                    'id_agent' => $agent->id_agent,
                    'type' => $type,
                ],
                [
                    'object_key' => $objectKey,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $user->id,
                ],
            );

            return $document->fresh(['uploader']);
        });
    }

    public function getDocument(Agent $agent, string $type): ?AgentDocument
    {
        if (! array_key_exists($type, AgentDocument::types())) {
            return null;
        }

        return AgentDocument::query()
            ->where('id_agent', $agent->id_agent)
            ->where('type', $type)
            ->first();
    }

    public function getObjectContents(AgentDocument $document): ?string
    {
        return $this->minio->getObjectContents($document->object_key);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function validationRulesForType(string $type): array
    {
        return match ($type) {
            AgentDocument::TYPE_CNI_RECTO,
            AgentDocument::TYPE_CNI_VERSO,
            AgentDocument::TYPE_PHOTO_IDENTITE => [
                'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:10240'],
            ],
            AgentDocument::TYPE_CONTRAT => [
                'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:15360'],
            ],
            default => throw new InvalidArgumentException('Type de document invalide.'),
        };
    }
}
