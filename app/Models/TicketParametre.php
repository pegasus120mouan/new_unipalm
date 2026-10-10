<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TicketParametre extends Model
{
    protected $table = 'ticket_parametres';

    protected $fillable = [
        'exiger_verification',
    ];

    protected function casts(): array
    {
        return [
            'exiger_verification' => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'exiger_verification' => false,
        ]);
    }

    public static function requiresVerification(): bool
    {
        return Cache::rememberForever('ticket_parametres.exiger_verification', function () {
            return (bool) static::instance()->exiger_verification;
        });
    }

    public static function setRequiresVerification(bool $required): void
    {
        $parametre = static::instance();
        $parametre->exiger_verification = $required;
        $parametre->save();

        Cache::forget('ticket_parametres.exiger_verification');
    }
}
