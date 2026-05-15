<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone', 30);
            $table->string('recipient_name')->nullable();
            $table->string('type', 50);
            $table->text('message');
            $table->string('status', 20)->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
