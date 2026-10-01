<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['socios', 'medidores', 'lecturas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'latitud')) {
                    $table->decimal('latitud', 10, 7)->nullable()->after($this->afterColumn($tableName));
                }

                if (! Schema::hasColumn($tableName, 'longitud')) {
                    $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
                }
            });

        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (['socios', 'medidores', 'lecturas'] as $tableName) {
                DB::statement("CREATE INDEX IF NOT EXISTS idx_{$tableName}_geolocation ON {$tableName} (latitud, longitud)");
            }

            DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_medidores_consumo AS
SELECT
    m.id_medidor,
    m.numero_serie,
    m.id_socio,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_usuario,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    COALESCE(sec.nombre, 'Sin zona') AS zona,
    COALESCE(s.direccion, 'Sin direccion') AS direccion,
    COALESCE(ul.lectura_actual, 0) AS lectura_sugerida,
    ul.fecha_lectura AS ultima_fecha,
    m.latitud AS medidor_latitud,
    m.longitud AS medidor_longitud,
    s.latitud AS socio_latitud,
    s.longitud AS socio_longitud
FROM medidores m
LEFT JOIN socios s ON s.id_socio = m.id_socio
LEFT JOIN personas p ON p.id_persona = s.id_persona
LEFT JOIN sectores sec ON sec.id_sector = s.id_sector
LEFT JOIN LATERAL (
    SELECT l.lectura_actual, l.fecha_lectura
    FROM lecturas l
    WHERE l.id_medidor = m.id_medidor
    ORDER BY l.fecha_lectura DESC, l.id_lectura DESC
    LIMIT 1
) ul ON TRUE
WHERE m.estado = 'activo'
SQL);

            DB::statement(<<<'SQL'
CREATE OR REPLACE VIEW v_tecnico_socios_catalogo AS
SELECT
    s.id_socio,
    COALESCE(s.numero_socio, 'SOC-' || LPAD(s.id_socio::text, 4, '0')) AS codigo_display,
    TRIM(COALESCE(p.nombres, '') || ' ' || COALESCE(p.apellidos, '')) AS socio_nombre,
    p.cedula_identidad,
    s.id_sector,
    COALESCE(sec.nombre, 'Sin zona') AS sector_nombre,
    COALESCE(sec.zona, sec.nombre, 'Sin zona') AS zona,
    COALESCE(s.direccion, 'Sin direccion') AS direccion,
    s.estado,
    s.latitud,
    s.longitud
FROM socios s
LEFT JOIN personas p ON p.id_persona = s.id_persona
LEFT JOIN sectores sec ON sec.id_sector = s.id_sector
SQL);
        } else {
            foreach (['socios', 'medidores', 'lecturas'] as $tableName) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->index(['latitud', 'longitud'], 'idx_'.$tableName.'_geolocation');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['lecturas', 'medidores', 'socios'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex('idx_'.$tableName.'_geolocation');
                $table->dropColumn(['latitud', 'longitud']);
            });
        }
    }

    private function afterColumn(string $tableName): string
    {
        return match ($tableName) {
            'socios' => 'direccion',
            'medidores' => 'fecha_instalacion',
            'lecturas' => 'observaciones',
            default => 'id',
        };
    }
};
