<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'carlos.mamani@aguapotable.bo' => 'Admin2025!',
            'rosa.flores@aguapotable.bo' => 'Secret2025!',
            'pedro.condori@aguapotable.bo' => 'Tecnic2025!',
        ];

        DB::table('users')
            ->whereIn('email', array_keys($defaults))
            ->orderBy('id')
            ->each(function (object $user) use ($defaults): void {
                $defaultPassword = $defaults[$user->email] ?? null;

                if ($defaultPassword && Hash::check($defaultPassword, $user->password)) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'must_change_password' => true,
                            'updated_at' => now(),
                        ]);

                    User::find($user->id)?->flushAuthCache();
                }
            });
    }

    public function down(): void
    {
        // Never re-enable known default credentials automatically.
    }
};
