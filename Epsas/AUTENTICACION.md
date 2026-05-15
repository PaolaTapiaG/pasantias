# Sistema de Autenticación EPSAS - Guía de Instalación

## 📋 Descripción
Se ha implementado un sistema completo de autenticación con login y dashboard en el proyecto EPSAS utilizando Laravel.

## 🔧 Componentes Creados

### 1. **AuthController** (`app/Http/Controllers/AuthController.php`)
- `showLogin()` - Muestra la vista de login
- `login()` - Procesa el formulario de login
- `logout()` - Cierra la sesión

### 2. **Vista de Login** (`resources/views/auth/login.blade.php`)
- Formulario con validación
- Estilos modernos con Tailwind CSS
- Manejo de errores
- Opción "Recuérdame"

### 3. **Dashboard** (`resources/views/dashboard/index.blade.php`)
- Panel de bienvenida
- Menú de opciones según rol
- Opción de cerrar sesión
- Diseño responsive

### 4. **Layout Principal** (`resources/views/layouts/app.blade.php`)
- Estructura HTML5
- Estilos CSS integrados
- Compatible con Blade templates

### 5. **Rutas Configuradas** (`routes/web.php`)
```
GET  /login          → Mostrar formulario de login
POST /login          → Procesar login
GET  /dashboard      → Panel de usuario (requiere autenticación)
POST /logout         → Cerrar sesión
```

## 🚀 Pasos para Usar

### 1. Instalar Dependencias
```bash
composer install
npm install
```

### 2. Configurar Variables de Entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configurar Base de Datos
Editar `.env` con tus credenciales:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=epsas
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Migrar Base de Datos
```bash
php artisan migrate
```

### 5. Crear Usuario de Prueba (Opcional)
```bash
php artisan db:seed
```

Esto creará un usuario:
- **Email:** test@example.com
- **Contraseña:** password (generada por UserFactory)

Para ver la contraseña generada:
1. Ve a `database/factories/UserFactory.php`
2. O ejecuta `php artisan tinker` y crea un usuario manualmente

### 6. Compilar Assets
```bash
npm run build
# O para desarrollo:
npm run dev
```

### 7. Iniciar el Servidor
```bash
php artisan serve
```

El servidor estará disponible en: `http://localhost:8000`

## 🔐 Acceder al Sistema

1. Ve a `http://localhost:8000/login`
2. Ingresa las credenciales:
   - **Email:** test@example.com
   - **Contraseña:** (ver en el UserFactory o base de datos)
3. Se redirigirá al dashboard

## 📝 Crear Nuevo Usuario (Desde Tinker)

```bash
php artisan tinker
```

Luego ejecuta:
```php
App\Models\User::create([
    'name' => 'Tu Nombre',
    'email' => 'tu@email.com',
    'password' => bcrypt('tu_contraseña')
]);
```

## 🛡️ Características de Seguridad

✅ Validación CSRF (protección contra ataques)
✅ Contraseñas hasheadas
✅ Regeneración de sesión después de login
✅ Protección de rutas con middleware `auth`
✅ Protección de login con middleware `guest`

## 📱 Características de la Vista

- ✅ Responsivo (funciona en móvil, tablet y desktop)
- ✅ Validación frontend y backend
- ✅ Mensajes de error y éxito
- ✅ Opción "Recordarme"
- ✅ Diseño moderno con Tailwind CSS

## 🔄 Flujo de Autenticación

```
Visitante → GET /login (vista login)
         → POST /login (credenciales)
         → ¿Válidas? 
           └─ Sí → Regenerar sesión → Redirect /dashboard
           └─ No → Error → Volver a login
         → GET /dashboard (requiere auth)
         → POST /logout → Borrar sesión → Redirect /
```

## 📂 Estructura de Archivos Creados

```
app/Http/Controllers/
├── AuthController.php (NUEVO)

resources/views/
├── auth/
│   └── login.blade.php (ACTUALIZADO)
├── dashboard/
│   └── index.blade.php (ACTUALIZADO)
└── layouts/
    └── app.blade.php (ACTUALIZADO)

routes/
└── web.php (ACTUALIZADO)
```

## 🐛 Solución de Problemas

### La vista de login se ve sin estilos
```bash
npm run build
php artisan serve
```

### Error "SQLSTATE[HY000]"
Verifica tu archivo `.env` y la configuración de base de datos.

### Error "class 'AuthController' not found"
Asegúrate de que el archivo existe en `app/Http/Controllers/AuthController.php`

### Error "view 'auth.login' not found"
Verifica que exista `resources/views/auth/login.blade.php`

## 📞 Necesitas Ayuda?

Revisa la [documentación oficial de Laravel](https://laravel.com/docs)
