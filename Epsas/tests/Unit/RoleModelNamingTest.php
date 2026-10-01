<?php

namespace Tests\Unit;

use App\Models\AccessRole;
use App\Models\EmployeeRole;
use App\Models\Rol;
use App\Models\Role;
use Tests\TestCase;

class RoleModelNamingTest extends TestCase
{
    public function test_explicit_role_models_keep_the_existing_tables(): void
    {
        $this->assertSame('user_roles', (new AccessRole)->getTable());
        $this->assertSame('roles', (new EmployeeRole)->getTable());
        $this->assertSame('id_rol', (new EmployeeRole)->getKeyName());
    }

    public function test_legacy_role_names_remain_compatible_wrappers(): void
    {
        $this->assertInstanceOf(AccessRole::class, new Role);
        $this->assertInstanceOf(EmployeeRole::class, new Rol);
    }
}
