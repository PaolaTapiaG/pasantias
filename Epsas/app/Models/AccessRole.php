<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccessRole extends Model
{
    protected $table = 'user_roles';
    protected $fillable = ['name', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'user_roles_id', 'user_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role', 'user_roles_id', 'user_permissions_id');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
