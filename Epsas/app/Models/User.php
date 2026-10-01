<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Auth\CachedEloquentUserProvider;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(fn (self $user) => $user->flushAuthCache());
        static::deleted(fn (self $user) => $user->flushAuthCache());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'id_persona',
        'password',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    /**
     * Relación muchos a muchos con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AccessRole::class, 'role_user', 'user_id', 'user_roles_id');
    }

    /**
     * Verificar si el usuario tiene un rol
     */
    public function hasRole(string $role): bool
    {
        return $this->cachedRoleNames()->contains($role);
    }

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->cachedRoleNames()->intersect($roles)->isNotEmpty();
    }

    /**
     * Verificar si el usuario tiene todos los roles especificados
     */
    public function hasAllRoles($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->cachedRoleNames()->intersect($roles)->count() === count($roles);
    }

    /**
     * Verificar si el usuario tiene un permiso (a través de sus roles)
     */
    public function hasPermission(string $permission): bool
    {
        return $this->cachedPermissionNames()->contains($permission);
    }

    /**
     * Asignar un rol al usuario
     */
    public function assignRole($role): void
    {
        if (is_string($role)) {
            $role = AccessRole::where('name', $role)->firstOrFail();
        }

        if (! $this->roles()->where('user_roles.id', $role->id)->exists()) {
            $this->roles()->attach($role);
        }
    }

    /**
     * Remover un rol del usuario
     */
    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = AccessRole::where('name', $role)->firstOrFail();
        }
        $this->roles()->detach($role);
    }

    public function cachedRoleNames()
    {
        return Cache::remember(
            "user:{$this->getKey()}:role-names",
            now()->addMinutes((int) config('auth.user_cache_minutes', 1440)),
            fn () => $this->roles()->pluck('name')
        );
    }

    public function cachedPermissionNames()
    {
        return Cache::remember(
            "user:{$this->getKey()}:permission-names",
            now()->addMinutes((int) config('auth.user_cache_minutes', 1440)),
            fn () => $this->roles()
                ->with('permissions:id,name')
                ->get()
                ->flatMap(fn (AccessRole $role) => $role->permissions->pluck('name'))
                ->unique()
                ->values()
        );
    }

    public function flushAuthCache(): void
    {
        Cache::forget('auth:user:'.str_replace('\\', '.', static::class).':'.$this->getAuthIdentifier());
        Cache::forget("user:{$this->getKey()}:role-names");
        Cache::forget("user:{$this->getKey()}:permission-names");

        if ($this->email) {
            Cache::forget(CachedEloquentUserProvider::credentialCacheKey(['email' => mb_strtolower($this->email)]));
        }

        if ($this->username) {
            Cache::forget(CachedEloquentUserProvider::credentialCacheKey(['username' => mb_strtolower($this->username)]));
        }
    }
}
