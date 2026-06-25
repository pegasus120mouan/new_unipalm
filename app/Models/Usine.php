<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nom_usine',
    'created_by',
])]
class Usine extends Model
{
    protected $table = 'usines';

    protected $primaryKey = 'id_usine';

    public $timestamps = false;

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'id_usine', 'id_usine');
    }
}
