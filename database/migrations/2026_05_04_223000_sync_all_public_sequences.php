<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::select(<<<'SQL'
            select
                table_name,
                column_name,
                pg_get_serial_sequence(format('%I.%I', table_schema, table_name), column_name) as sequence_name
            from information_schema.columns
            where table_schema = 'public'
              and column_default like 'nextval(%'
            order by table_name, ordinal_position
        SQL);

        foreach ($columns as $column) {
            if (!$column->sequence_name) {
                continue;
            }

            $table = str_replace('"', '""', $column->table_name);
            $key = str_replace('"', '""', $column->column_name);

            $maxId = DB::scalar(sprintf(
                'select coalesce(max("%s"), 0) from "%s"',
                $key,
                $table
            ));

            DB::select(
                'select setval(?::regclass, ?, true)',
                [$column->sequence_name, max((int) $maxId, 1)]
            );
        }
    }

    public function down(): void
    {
        // No-op: sequence sync is derived from current data.
    }
};
