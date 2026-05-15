# Sistema de Roles y Permisos - EPSAS
## Documentación Completa

---

## 📋 Tabla de Contenidos
1. [Roles Disponibles](#roles-disponibles)
2. [Permisos](#permisos)
3. [Métodos en el Modelo User](#métodos-en-el-modelo-user)
4. [Proteger Rutas](#proteger-rutas)
5. [Ejemplos de Uso](#ejemplos-de-uso)
6. [Usuarios de Prueba](#usuarios-de-prueba)

---

## 🎭 Roles Disponibles

### 1. **ADMINISTRADOR** 🔴
- **Email**: carlos.mamani@aguapotable.bo
- **Contraseña**: Admin2025!
- **Permisos**: Acceso total al sistema

**Acceso a:**
- Gestión de usuarios
- Gestión de permisos y roles
- Configuración del sistema
- Auditoría de cambios
- Todas las operaciones normales

### 2. **SECRETARIA** 🟢
- **Email**: rosa.flores@aguapotable.bo
- **Contraseña**: Secret2025!
- **Permisos**: Gestión administrativa

**Acceso a:**
- Gestión de socios (clientes)
- Creación y edición de facturas
- Registro de cobros
- Historial de pagos
- Reportes financieros

### 3. **TECNICO** 🔵
- **Email**: pedro.condori@aguapotable.bo
- **Contraseña**: Tecnic2025!
- **Permisos**: Operaciones técnicas

**Acceso a:**
- Gestión de medidores
- Registro de lecturas
- Mantenimiento de equipos
- Reportes técnicos
- Historial de consumo

---

## 🔐 Permisos del Sistema

### Usuarios
- `ver_usuarios` - Ver lista de usuarios
- `crear_usuario` - Crear nuevo usuario
- `editar_usuario` - Editar usuario
- `eliminar_usuario` - Eliminar usuario

### Socios
- `ver_socios` - Ver lista de socios
- `crear_socio` - Crear nuevo socio
- `editar_socio` - Editar socio
- `eliminar_socio` - Eliminar socio

### Medidores
- `ver_medidores` - Ver lista de medidores
- `crear_medidor` - Crear nuevo medidor
- `editar_medidor` - Editar medidor
- `eliminar_medidor` - Eliminar medidor

### Lecturas
- `ver_lecturas` - Ver lista de lecturas
- `crear_lectura` - Registrar nueva lectura
- `editar_lectura` - Editar lectura
- `eliminar_lectura` - Eliminar lectura

### Facturas
- `ver_facturas` - Ver lista de facturas
- `crear_factura` - Crear nueva factura
- `editar_factura` - Editar factura
- `eliminar_factura` - Eliminar factura

### Cobros
- `ver_cobros` - Ver lista de cobros
- `crear_cobro` - Registrar nuevo cobro
- `editar_cobro` - Editar cobro
- `eliminar_cobro` - Eliminar cobro

### Reportes
- `ver_reportes` - Ver reportes
- `exportar_reportes` - Exportar reportes

### Configuración
- `ver_configuracion` - Ver configuración
- `editar_configuracion` - Editar configuración

---

## 👤 Métodos en el Modelo User

### Verificar Roles

```php
// Verificar si tiene un rol específico
if (auth()->user()->hasRole('administrador')) {
    // Código solo para administradores
}

// Verificar si tiene CUALQUIERA de los roles
if (auth()->user()->hasAnyRole(['administrador', 'secretaria'])) {
    // Código para admin o secretaria
}

// Verificar si tiene TODOS los roles
if (auth()->user()->hasAllRoles(['administrador', 'secretaria'])) {
    // Código solo si tiene ambos roles
}
```

### Verificar Permisos

```php
// Verificar si tiene un permiso específico
if (auth()->user()->hasPermission('crear_factura')) {
    // Mostrar botón crear factura
}
```

### Asignar/Remover Roles

```php
$user = User::find(1);

// Asignar un rol
$user->assignRole('tecnico');
$user->assignRole('secretaria');

// Remover un rol
$user->removeRole('tecnico');

// Ver todos los roles del usuario
$roles = auth()->user()->roles;  // Colección de roles
foreach ($roles as $role) {
    echo $role->name;
}
```

### Obtener Información del Usuario

```php
// Todos los roles del usuario
$roles = auth()->user()->roles;

// Contar roles
$roleCount = auth()->user()->roles->count();

// Acceder a un rol específico
$role = auth()->user()->roles()->where('name', 'administrador')->first();

// Ver permisos de los roles del usuario
foreach (auth()->user()->roles as $role) {
    foreach ($role->permissions as $permission) {
        echo $permission->name;
    }
}
```

---

## 🛡️ Proteger Rutas

### Con Middleware en Routes

#### 1. Proteger ruta para UN rol específico
```php
Route::middleware('role:administrador')->get('/admin', [AdminController::class, 'index']);
```

#### 2. Proteger ruta para MÚLTIPLES roles
```php
Route::middleware('role:administrador,secretaria')->get('/reportes', [ReportController::class, 'index']);
```

#### 3. Agrupar rutas por rol
```php
Route::middleware('role:administrador')->group(function () {
    Route::get('/usuarios', [UserController::class, 'index']);
    Route::post('/usuarios', [UserController::class, 'store']);
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);
});
```

### En un Controller

```php
<?php

namespace App\Http\Controllers;

class AdminController extends Controller
{
    public function __construct()
    {
        // Solo administradores
        $this->middleware('role:administrador');
    }

    public function index()
    {
        return view('admin.index');
    }
}
```

### En una Vista Blade

```blade
@if(auth()->user()->hasRole('administrador'))
    <a href="/admin/usuarios" class="btn btn-danger">
        Gestionar Usuarios
    </a>
@endif

@if(auth()->user()->hasPermission('crear_factura'))
    <button class="btn btn-success">
        Crear Nueva Factura
    </button>
@endif
```

---

## 💡 Ejemplos de Uso

### Ejemplo 1: Dashboard Personalizado

```blade
@extends('layouts.app')

@section('content')
    @if(auth()->user()->hasRole('administrador'))
        <div class="admin-dashboard">
            <h2>Panel de Administración</h2>
            <p>Usuarios activos: {{ \App\Models\User::count() }}</p>
        </div>
    @elseif(auth()->user()->hasRole('secretaria'))
        <div class="secretaria-dashboard">
            <h2>Facturas</h2>
            <!-- Contenido de secretaria -->
        </div>
    @elseif(auth()->user()->hasRole('tecnico'))
        <div class="tecnico-dashboard">
            <h2>Lecturas</h2>
            <!-- Contenido de técnico -->
        </div>
    @endif
@endsection
```

### Ejemplo 2: Verificación en Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Factura;

class FacturaController extends Controller
{
    public function create()
    {
        // Solo secretaria y admin pueden crear facturas
        if (!auth()->user()->hasAnyRole(['secretaria', 'administrador'])) {
            return abort(403);
        }

        return view('facturas.create');
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasPermission('crear_factura')) {
            abort(403, 'No tienes permiso para crear facturas');
        }

        // Crear factura
        $factura = Factura::create($request->all());

        return redirect()->route('facturas.show', $factura)->with('success', 'Factura creada');
    }
}
```

### Ejemplo 3: Menú Dinámico

```blade
<nav class="sidebar">
    <a href="/dashboard">Dashboard</a>

    @if(auth()->user()->hasRole('administrador'))
        <hr>
        <h4>Administración</h4>
        <a href="/admin/usuarios">Usuarios</a>
        <a href="/admin/configuracion">Configuración</a>
    @endif

    @if(auth()->user()->hasRole('secretaria'))
        <hr>
        <h4>Gestión Administrativa</h4>
        <a href="/admin/socios">Socios</a>
        <a href="/admin/facturas">Facturas</a>
        <a href="/admin/cobros">Cobros</a>
    @endif

    @if(auth()->user()->hasRole('tecnico'))
        <hr>
        <h4>Operaciones Técnicas</h4>
        <a href="/admin/medidores">Medidores</a>
        <a href="/admin/lecturas">Lecturas</a>
    @endif
</nav>
```

### Ejemplo 4: Autorización en Policies (Avanzado)

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Factura;

class FacturaPolicy
{
    public function view(User $user, Factura $factura)
    {
        return $user->hasPermission('ver_facturas');
    }

    public function create(User $user)
    {
        return $user->hasPermission('crear_factura');
    }

    public function update(User $user, Factura $factura)
    {
        return $user->hasPermission('editar_factura');
    }

    public function delete(User $user, Factura $factura)
    {
        return $user->hasPermission('eliminar_factura');
    }
}
```

---

## 🧪 Usuarios de Prueba

| Nombre | Email | Contraseña | Rol |
|--------|-------|-----------|-----|
| Carlos Alberto Mamani | carlos.mamani@aguapotable.bo | Admin2025! | Administrador |
| Rosa Elena Flores | rosa.flores@aguapotable.bo | Secret2025! | Secretaria |
| Pedro Luis Condori | pedro.condori@aguapotable.bo | Tecnic2025! | Técnico |
| Test User | test@example.com | password | (Sin rol asignado) |

---

## 🔄 Asignar Roles a Nuevos Usuarios

```php
// En un Controller
$user = User::create([
    'name' => 'Juan Pérez',
    'email' => 'juan@example.com',
    'password' => Hash::make('password'),
]);

// Asignar rol
$user->assignRole('tecnico');

// O múltiples roles
$user->assignRole('administrador');
$user->assignRole('secretaria');
```

---

## 🚀 Rutas Protegidas Actualmente

### Administrador
- GET `/admin/usuarios` - Ver usuarios
- GET `/admin/permisos` - Gestionar permisos
- GET `/admin/configuracion` - Configuración
- GET `/admin/auditoria` - Ver auditoría

### Secretaria
- GET `/admin/socios` - Ver socios
- GET `/admin/facturas` - Ver facturas
- POST `/admin/facturas` - Crear factura
- GET `/admin/cobros` - Ver cobros
- POST `/admin/cobros` - Registrar cobro
- GET `/admin/reportes` - Ver reportes

### Técnico
- GET `/admin/medidores` - Ver medidores
- POST `/admin/medidores` - Crear medidor
- GET `/admin/lecturas` - Ver lecturas
- POST `/admin/lecturas` - Registrar lectura
- GET `/admin/mantenimiento` - Ver mantenimiento
- GET `/admin/reportes-tecnicos` - Reportes técnicos

---

## 📞 Soporte

Para más información sobre Laravel Authorization:
- [Laravel Authorization Docs](https://laravel.com/docs/authorization)
- [Middleware Personalizado](https://laravel.com/docs/middleware)
