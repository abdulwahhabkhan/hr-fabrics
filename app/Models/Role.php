<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public static function permissionsByRole($role_id): array|Collection
    {
        if (! $role_id) {
            return collect();
        }

        $permissions = Cache::get(self::modelCacheKey('2_'.$role_id));
        if (! $permissions) {
            $role = self::find($role_id);
            if (! $role) {
                return collect();
            }
            $permissions = $role->permissions->map(fn ($item, $key) => [$item['name'], $item['section'], $item['module']])->collapse()->unique()->values();
            Cache::put(self::modelCacheKey('2_'.$role_id), $permissions, 4 * 60 * 60);
        }

        return $permissions;
    }

    public static function clearCache($role_id): bool
    {
        Cache::forget(self::modelCacheKey($role_id));

        return true;
    }

    public static function rolePermissions($role_id)
    {
        if (! $role_id) {
            return [];
        }

        $permissions = Cache::get(self::modelCacheKey($role_id));
        if (! $permissions) {
            $role = self::find($role_id);
            if (! $role) {
                return [];
            }
            $permissions = $role->permissions->groupby('name')->toArray();
            Cache::put(self::modelCacheKey($role_id), $permissions);
        }

        return $permissions;
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::updated(function () {
            Cache::forget(self::class);
        });
    }

    /**
     * Filter based on request params
     *
     * @return Builder mixed
     */
    #[Scope]
    protected function filter(Builder $query, Request $params): Builder
    {
        if ($params->has('search')) {
            $query->where('name', 'like', '%'.$params->get('search').'%');
        }

        return $query;
    }
}
