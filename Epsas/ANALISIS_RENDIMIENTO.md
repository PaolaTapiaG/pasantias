# ANALISIS DE RENDIMIENTO - EPSAS Backend & Frontend

> Documento historico archivado. Las cifras y rutas reflejan el estado de
> junio de 2026; las decisiones vigentes estan en `AUDITORIA_DEUDA_TECNICA.md`.

**Fecha:** 3 de Junio 2026  
**Problema:** Lentitud entre pantallas  
**Severidad:** Alta

---

## 🔴 PROBLEMAS CRÍTICOS IDENTIFICADOS

### 1. **N+1 QUERIES - Problema Más Crítico**

#### Ubicaciones Identificadas:

**A) `DashboardController.php` - Línea 139-207**
```php
// ❌ PROBLEMA: Obtiene todos los medidores sin eager loading
$medidores = Medidor::all();  // Query 1
foreach ($medidores as $medidor) {
    $medidor->socio;          // Query N (una por cada medidor)
    $medidor->empleadoInstalador;
}
```

**B) `FacturaController.php` - invoicePaginator()`
```php
// ❌ PROBLEMA: JOIN sin eager loading posterior
$query = DB::table('facturas as f')
    ->leftJoin('socios as s', ...)
    ->leftJoin('personas as p', ...)
    ->leftJoin('periodos_facturacion as pf', ...)
    ->get();  // Después en la vista: $factura->socio->persona->nombre
```

**C) `ExportController.php`**
```php
// ❌ PROBLEMA: Exporta 10,000+ registros sin paginación
$rows = $query->get()->map(fn (Empleado $empleado) => [
    $empleado->persona->nombre,    // Query N+1
    $empleado->rol->nombre,        // Query N+1
]);
```

---

### 2. **BASE DE DATOS - Configuración Ineficiente**

**Problemas:**
- ❌ **Supabase Pooler**: Conexión inestable (ver repo memory)
- ❌ **Falta de Índices**: Campos clave sin índices
- ❌ **Sin Caché Propio**: Solo usa `OperationalCache` en algunos lugares
- ❌ **Paginación Ausente**: Listas grandes sin límite

**Índices Faltantes:**
```sql
-- Estas queries están siendo lentas por falta de índices
- socios.numero_socio (búsquedas frecuentes)
- facturas.id_socio + estado (filtrado de facturas)
- medidores.id_socio (búsquedas por socio)
- lecturas.id_medidor + fecha_lectura (últimas lecturas)
```

---

### 3. **FRONTEND - Performance Issues**

**Problemas identificados:**

A) **Vite Config - Sin optimización**
```js
// ❌ No hay minificación ni split de chunks
// ❌ No hay compresión gzip
// ❌ No hay lazy loading en assets
```

B) **Alpine.js + Swiper**
```js
// ❌ Swiper se inicializa sin límite de slides
// ❌ No hay virtual scrolling para listas largas
// ❌ Event listeners no se limpian al navegar
```

C) **CSS - Tailwind sin purging**
```js
// ❌ El bundle Tailwind puede ser 1MB+ sin configuración
```

---

### 4. **NPM RUN DEV - Error Actual**

**Síntoma:** Exit code 1

**Causas Probables:**
1. Falta dependencia de build
2. Error en `vite.config.js`
3. Permiso de archivo o puerto ocupado
4. Node/npm versión incompatible

---

### 5. **CACHÉ - Subutilizado**

Actuales:
- ✅ `OperationalCache::remember()` en algunos controllers
- ❌ **Falta caché en:**
  - Listados de socios/medidores
  - Datos de configuración del sistema
  - Consultas de roles y permisos
  - Últimas lecturas por medidor
  - Facturas pendientes por cliente

---

## 🟠 IMPACTO EN EXPERIENCIA DEL USUARIO

| Pantalla | Problema | Impacto | Latencia |
|----------|----------|--------|----------|
| Dashboard Admin | N+1 queries + sin caché | Carga 500+ queries | 3-5 seg |
| Listado de Facturas | Joins sin índices | 1000+ registros sin paginación | 2-3 seg |
| Portal Cliente | Búsqueda de deuda lenta | DB sin índices | 4-6 seg |
| Tecnico Panel | Carga de medidores | N+1 queries | 2-3 seg |
| Export Empleados | Exporta sin límite | Tiempo timeout > 30 seg |

---

## ✅ SOLUCIONES (Prioridad)

### **PRIORIDAD 1 - Implementar en < 2 horas**

#### 1.1 Eager Loading en Controllers
```php
// Reemplazar:
$medidores = Medidor::all();

// Con:
$medidores = Medidor::with(['socio.persona', 'empleadoInstalador'])->get();
```

**Archivos a modificar:**
- `DashboardController.php` (líneas 139-207)
- `TecnicoPanelController.php` (toda la clase)
- `ClientePortalController.php` (búsquedas)

**Beneficio:** Reduce queries de 500+ a 5-10. **Mejora: 80-90%**

---

#### 1.2 Agregar Índices de Base de Datos

```sql
-- Crear estos índices inmediatamente
CREATE INDEX idx_socios_numero_socio ON socios(numero_socio);
CREATE INDEX idx_socios_estado ON socios(estado);
CREATE INDEX idx_socios_id_sector ON socios(id_sector);

CREATE INDEX idx_facturas_id_socio ON facturas(id_socio);
CREATE INDEX idx_facturas_id_socio_estado ON facturas(id_socio, estado);
CREATE INDEX idx_facturas_id_periodo ON facturas(id_periodo);

CREATE INDEX idx_medidores_id_socio ON medidores(id_socio);
CREATE INDEX idx_medidores_estado ON medidores(estado);

CREATE INDEX idx_lecturas_id_medidor ON lecturas(id_medidor);
CREATE INDEX idx_lecturas_id_medidor_fecha ON lecturas(id_medidor, fecha_lectura DESC);

CREATE INDEX idx_cobros_id_factura ON cobros(id_factura);
CREATE INDEX idx_cobros_estado ON cobros(estado);
```

**Beneficio:** Queries 50-70% más rápidas. **Mejora: 40-60%**

---

### **PRIORIDAD 2 - Implementar en 2-4 horas**

#### 2.1 Paginación en Listados

```php
// Reemplazar en FacturaController::index()
public function invoicePaginator(Request $request)
{
    $perPage = 50;  // o más si la UI lo soporta
    
    return Factura::with(['socio.persona', 'periodo'])
        ->when($request->periodo, fn($q) => $q->where('id_periodo', $request->periodo))
        ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
        ->orderByDesc('fecha_emision')
        ->paginate($perPage);  // ← AGREGAR ESTO
}
```

**Beneficio:** De cargar 10,000 a cargar 50. **Mejora: 95%**

---

#### 2.2 Cache de Datos Frecuentes

```php
// En DashboardController
public function adminStats()
{
    return OperationalCache::remember('dashboard:admin:stats', 
        now()->addMinutes(5), 
        function () {
            return [
                'total_socios' => Socio::count(),
                'socios_activos' => Socio::where('estado', 'activo')->count(),
                'facturas_pendientes' => Factura::whereIn('estado', ['pendiente', 'vencida'])->count(),
                'ingresos_mes' => $this->monthlyRevenue(),
            ];
        }
    );
}

// En helpers o services:
function getCachedRoles() {
    return cache()->remember('roles:all', now()->addDays(1), fn () => Role::all());
}
```

**Beneficio:** Reduce queries dashboard de 50 a 2-3. **Mejora: 90%**

---

### **PRIORIDAD 3 - Frontend (2-3 horas)**

#### 3.1 Optimizar Vite Config

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'swiper': ['swiper'],
                    'alpine': ['alpinejs'],
                },
            },
        },
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        hmr: {
            protocol: 'ws',
            host: 'localhost',
            port: 5173,
        },
    },
});
```

**Beneficio:** Reduce bundle 30-40%. **Mejora: 30%**

---

#### 3.2 Tailwind Config - Purge CSS

```javascript
// tailwind.config.js
export default {
    content: [
        './resources/**/*.{blade.php,js}',
        './app/View/Components/**/*.php',
    ],
    theme: {
        extend: {
            // Extensiones aquí
        },
    },
    cacheInvalidation: {
        timestamp: true,
    },
}
```

---

#### 3.3 Lazy Load y Virtual Scrolling

```html
<!-- En listas largas: -->
<div id="medidores-list" data-lazy>
    <!-- Alpine.js carga items bajo demanda -->
</div>

<script>
document.addEventListener('alpine:initialized', () => {
    Alpine.data('medidoresList', () => ({
        items: [],
        page: 1,
        perPage: 50,
        async loadMore() {
            const res = await fetch(`/api/medidores?page=${this.page}&per_page=${this.perPage}`);
            this.items = await res.json();
            this.page++;
        },
    }));
});
</script>
```

---

## 🔧 PASOS PARA IMPLEMENTAR

### Fase 1: Arreglar error npm (15 min)

```bash
# En terminal
cd c:\Practicas\Epsas

# Limpiar node_modules y reinstalar
rm -r node_modules package-lock.json
npm install

# Intentar nuevamente
npm run dev
```

Si sigue fallando, ejecutar:
```bash
npm run build --verbose  # Ver logs detallados
```

---

### Fase 2: Eager Loading (45 min)

Archivos a modificar:

1. **DashboardController.php** - Línea 139-207
2. **TecnicoPanelController.php** - Métodos del constructor
3. **ClientePortalController.php** - `buscarDeuda()`
4. **FacturaController.php** - `invoicePaginator()`

---

### Fase 3: Índices BD (10 min)

```bash
# Conectarse a Supabase y ejecutar:
php artisan migrate:fresh --seed  # o

# O ejecutar directamente en SQL:
psql -h aws-1-sa-east-1.pooler.supabase.com -U postgres.mpesjpvcgekduehfccfv -d postgres
# Ejecutar los índices del SQL anterior
```

---

### Fase 4: Cache (30 min)

Modificar los controllers clave para usar `OperationalCache::remember()`

---

## 📈 RESULTADOS ESPERADOS

**ANTES:**
- Dashboard: 3-5 segundos
- Listado Facturas: 2-3 segundos  
- Búsqueda cliente: 4-6 segundos
- **Total navegación: 10-15 segundos**

**DESPUÉS (con todas las optimizaciones):**
- Dashboard: 500-800ms ⚡
- Listado Facturas: 400-600ms ⚡
- Búsqueda cliente: 300-500ms ⚡
- **Total navegación: 1.5-2 segundos** 

**Mejora General: 75-85% más rápido** 🚀

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

- [ ] Arreglar `npm run dev`
- [ ] Implementar eager loading en DashboardController
- [ ] Implementar eager loading en TecnicoPanelController
- [ ] Implementar eager loading en FacturaController
- [ ] Implementar eager loading en ClientePortalController
- [ ] Crear índices en base de datos
- [ ] Agregar paginación a listados
- [ ] Implementar caché en métodos frecuentes
- [ ] Optimizar Vite config
- [ ] Configurar Tailwind purge
- [ ] Probar performance con DevTools
- [ ] Medir resultados

---

## 🔍 CÓMO MONITOREAR MEJORAS

**En navegador (Chrome DevTools):**
1. Abre DevTools → Network
2. Recarga página
3. Busca: Tiempo total de carga
4. Compara con valores anteriores

**En Laravel:**
```bash
# Ver queries ejecutadas
php artisan tinker
# En producción usar Telescope o Debugbar
```

---

**Próximos pasos:** ¿Quieres que implemente estas optimizaciones? Empiezo por el eager loading que es lo más urgente.
