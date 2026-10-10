<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function tokenable(): MorphTo
    {
        return $this->morphTo('tokenable')->withoutGlobalScopes();
    }
}
