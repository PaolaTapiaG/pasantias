<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_retention_events', function (Blueprint $table) {
            $table->id('id_retention_event');
            $table->string('media_type', 60);
            $table->string('record_table', 80);
            $table->unsignedBigInteger('record_id');
            $table->char('path_hash', 64);
            $table->unsignedInteger('retention_days');
            $table->string('action', 30);
            $table->timestampTz('executed_at')->nullable();
            $table->timestampsTz();

            $table->index(['record_table', 'record_id']);
            $table->index(['media_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_retention_events');
    }
};
