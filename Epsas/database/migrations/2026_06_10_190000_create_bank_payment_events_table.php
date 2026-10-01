<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_payment_events', function (Blueprint $table) {
            $table->id('id_event');
            $table->string('provider', 80);
            $table->string('event_id', 120);
            $table->string('event_type', 80);
            $table->string('order_code', 30);
            $table->string('reference', 120);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('BOB');
            $table->string('status', 30)->default('received');
            $table->char('payload_hash', 64);
            $table->json('payload');
            $table->timestampTz('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('id_orden_pago')->nullable()->constrained('ordenes_pago', 'id_orden_pago')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['provider', 'event_id'], 'bank_payment_event_unique');
            $table->index(['order_code', 'status']);
            $table->index(['status', 'created_at']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE bank_payment_events ADD CONSTRAINT chk_bank_event_status CHECK (status IN ('received', 'processed', 'failed'))");
            DB::statement('ALTER TABLE bank_payment_events ADD CONSTRAINT chk_bank_event_amount CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_payment_events');
    }
};
