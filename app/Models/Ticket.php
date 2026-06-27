<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'numero_ticket',
    'id_usine',
    'date_ticket',
    'id_agent',
    'vehicule_id',
    'poids',
    'id_utilisateur',
    'prix_unitaire',
    'date_validation_boss',
    'montant_paie',
    'date_paie',
    'montant_payer',
    'montant_reste',
    'statut_ticket',
    'numero_bordereau',
    'verification',
    'date_verification',
    'verifie_par',
    'created_at',
])]
class Ticket extends Model
{
    protected $table = 'tickets';

    protected $primaryKey = 'id_ticket';

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'date_ticket' => 'date',
            'date_validation_boss' => 'datetime',
            'date_paie' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'poids' => 'float',
            'prix_unitaire' => 'decimal:2',
            'montant_paie' => 'decimal:2',
            'montant_payer' => 'decimal:2',
            'montant_reste' => 'decimal:2',
            'verification' => 'boolean',
            'date_verification' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent', 'id_agent');
    }

    public function usine(): BelongsTo
    {
        return $this->belongsTo(Usine::class, 'id_usine', 'id_usine');
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id', 'vehicules_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('date_validation_boss');
    }

    public function scopeValidated($query)
    {
        return $query->whereNotNull('date_validation_boss')
            ->whereNotNull('prix_unitaire')
            ->where('prix_unitaire', '>', 0);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification', true);
    }

    public function isVerified(): bool
    {
        return (bool) $this->verification;
    }

    public function scopePaid($query)
    {
        return $query->whereNotNull('date_paie');
    }

    public function isValidated(): bool
    {
        return $this->date_validation_boss !== null && $this->hasPrixUnitaire();
    }

    public function isPending(): bool
    {
        return ! $this->isValidated();
    }

    public function hasPrixUnitaire(): bool
    {
        return ! blank($this->prix_unitaire) && (float) $this->prix_unitaire > 0;
    }

    public function isPaid(): bool
    {
        return $this->date_paie !== null;
    }

    public function scopeWithoutPrixUnitaire($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('prix_unitaire')
                ->orWhere('prix_unitaire', 0);
        });
    }

    public function scopeVisibleToCurrentUser($query)
    {
        $user = auth()->user();

        if ($user?->limitsTicketsToOwn()) {
            $query->where('id_utilisateur', $user->id);
        }

        return $query;
    }

    public function scopeEligibleForBordereau($query, int $idAgent, string $dateDebut, string $dateFin)
    {
        return $query
            ->where('id_agent', $idAgent)
            ->whereDate('created_at', '>=', $dateDebut)
            ->whereDate('created_at', '<=', $dateFin)
            ->validated()
            ->verified()
            ->where(function ($query) {
                $query->whereNull('numero_bordereau')
                    ->orWhere('numero_bordereau', '');
            });
    }
}
