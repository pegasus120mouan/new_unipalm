<?php

namespace App\Models;

use App\Services\MinioStorageService;
use App\Services\RolePermissionService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['nom', 'prenoms', 'contact', 'login', 'avatar', 'password', 'role', 'statut_compte'])]
#[Hidden(['password'])]
class Utilisateur extends Authenticatable
{
    use HasApiTokens;
    public const DEFAULT_PASSWORD = 'Unipalm@@2026';

    protected $table = 'utilisateurs';

    protected function casts(): array
    {
        return [
            'statut_compte' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->formatted_nom} {$this->formatted_prenoms}");
    }

    public function getFormattedNomAttribute(): string
    {
        return self::formatPersonName($this->nom);
    }

    public function getFormattedPrenomsAttribute(): string
    {
        return self::formatPersonName($this->prenoms);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->resolveMinioObjectKey() === null) {
            $avatar = $this->avatar ?: 'default.jpg';

            if ($avatar !== 'default.jpg' && file_exists(public_path('dossiers_images/'.$avatar))) {
                $version = filemtime(public_path('dossiers_images/'.$avatar)) ?: time();

                return asset('dossiers_images/'.$avatar).'?v='.$version;
            }

            return null;
        }

        $version = rawurlencode(basename((string) $this->avatar));

        return route('utilisateurs.avatar', $this).'?v='.$version;
    }

    public function resolveMinioObjectKey(): ?string
    {
        $avatar = trim((string) ($this->avatar ?? ''));

        if ($avatar === '' || $avatar === 'default.jpg') {
            return null;
        }

        $prefix = app(MinioStorageService::class)->usersPrefix().'/';

        if (str_starts_with($avatar, $prefix)) {
            return $avatar;
        }

        if (! str_contains($avatar, '/')) {
            return $prefix.$avatar;
        }

        return $avatar;
    }

    public function hasAvatarImage(): bool
    {
        if ($this->resolveMinioObjectKey() !== null && app(MinioStorageService::class)->isConfigured()) {
            return true;
        }

        $avatar = $this->avatar ?: 'default.jpg';

        return $avatar !== 'default.jpg'
            && file_exists(public_path('dossiers_images/'.$avatar));
    }

    public static function formatPersonName(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    public function checkPassword(string $plainPassword): bool
    {
        return hash('sha256', $plainPassword) === $this->password;
    }

    public function setPasswordFromPlain(string $plainPassword): void
    {
        $this->update([
            'password' => hash('sha256', $plainPassword),
        ]);
    }

    public function isActive(): bool
    {
        return (bool) $this->statut_compte;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin' => 'Administrateur',
            'operateur' => 'Opérateur',
            'validateur' => 'Validateur',
            'caissiere' => 'Caissière',
            'directeur' => 'Directeur',
            default => ucfirst((string) $this->role),
        };
    }

    public static function roleOptions(): array
    {
        return [
            'admin' => 'Administrateur',
            'operateur' => 'Opérateur',
            'validateur' => 'Validateur',
            'caissiere' => 'Caissière',
            'directeur' => 'Directeur',
        ];
    }

    public function canAccessModule(string $module): bool
    {
        $service = app(RolePermissionService::class);

        if ($this->role === 'admin' && in_array($module, $service->allModuleKeys(), true)) {
            return true;
        }

        return $service->roleCan((string) $this->role, $module);
    }

    public function canAccessAnyModule(array $modules): bool
    {
        foreach ($modules as $module) {
            if ($this->canAccessModule($module)) {
                return true;
            }
        }

        return false;
    }

    public function limitsTicketsToOwn(): bool
    {
        return $this->role === 'operateur';
    }

    public function canValidateTickets(): bool
    {
        return $this->role !== 'operateur';
    }
}
