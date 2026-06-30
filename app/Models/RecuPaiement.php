<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecuPaiement extends Model
{
    protected $table = 'recus_paiements';

    protected $primaryKey = 'id_recu';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'date_creation' => 'datetime',
            'montant_total' => 'decimal:2',
            'montant_paye' => 'decimal:2',
            'montant_precedent' => 'decimal:2',
            'reste_a_payer' => 'decimal:2',
        ];
    }

    public function sourceLabel(): string
    {
        return match ($this->source_paiement) {
            'transactions' => 'Caisse',
            'financement' => 'Financement',
            'cheque' => 'Chèque',
            default => (string) $this->source_paiement,
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type_document) {
            'ticket' => 'Ticket',
            'bordereau' => 'Bordereau',
            default => ucfirst((string) $this->type_document),
        };
    }
}
