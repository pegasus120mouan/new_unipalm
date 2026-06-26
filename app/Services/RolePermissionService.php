<?php

namespace App\Services;

use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;

class RolePermissionService
{
    public function allModuleKeys(): array
    {
        return collect(config('modules.groups', []))
            ->flatMap(fn (array $group) => array_keys($group['modules']))
            ->values()
            ->all();
    }

    public function modulesForRole(string $role): array
    {
        return Cache::remember($this->cacheKey($role), 3600, function () use ($role) {
            return RolePermission::query()
                ->where('role', $role)
                ->pluck('module')
                ->all();
        });
    }

    public function roleCan(string $role, string $module): bool
    {
        return in_array($module, $this->modulesForRole($role), true);
    }

    public function roleCanAny(string $role, array $modules): bool
    {
        foreach ($modules as $module) {
            if ($this->roleCan($role, $module)) {
                return true;
            }
        }

        return false;
    }

    public function moduleForRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        $map = config('modules.route_map', []);

        if (isset($map[$routeName])) {
            return $map[$routeName];
        }

        foreach ($map as $pattern => $module) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -2);
                if (str_starts_with($routeName, $prefix)) {
                    return $module;
                }
            }
        }

        return null;
    }

    public function sync(string $role, array $modules): void
    {
        $validModules = $this->allModuleKeys();
        $modules = array_values(array_unique(array_intersect($modules, $validModules)));

        RolePermission::query()->where('role', $role)->delete();

        foreach ($modules as $module) {
            RolePermission::query()->create([
                'role' => $role,
                'module' => $module,
            ]);
        }

        $this->forgetCache($role);
    }

    public function forgetCache(string $role): void
    {
        Cache::forget($this->cacheKey($role));
    }

    private function cacheKey(string $role): string
    {
        return 'role_permissions:'.$role;
    }
}
