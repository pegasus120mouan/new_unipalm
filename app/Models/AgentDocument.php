<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_agent',
    'type',
    'object_key',
    'original_name',
    'mime_type',
    'file_size',
    'uploaded_by',
])]
class AgentDocument extends Model
{
    public const TYPE_CNI_RECTO = 'cni_recto';

    public const TYPE_CNI_VERSO = 'cni_verso';

    public const TYPE_PHOTO_IDENTITE = 'photo_identite';

    public const TYPE_CONTRAT = 'contrat';

    protected $table = 'agent_documents';

    protected $primaryKey = 'id_document';

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_CNI_RECTO => 'CNI (recto)',
            self::TYPE_CNI_VERSO => 'CNI (verso)',
            self::TYPE_PHOTO_IDENTITE => 'Photo d\'identité',
            self::TYPE_CONTRAT => 'Contrat',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? (string) $this->type;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'uploaded_by');
    }
}
