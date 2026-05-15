<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE personas
  ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE;
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE personas
  DROP COLUMN IF EXISTS fecha_nacimiento;
SQL);
    }
};
