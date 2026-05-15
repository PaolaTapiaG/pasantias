<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->select('id', 'email', 'username')->get();

        foreach ($users as $user) {
            $email = $user->email ? Str::lower($user->email) : null;
            $username = $user->username;

            if (!$username && $email) {
                $base = Str::slug(Str::before($email, '@'), '_');
                $username = $base !== '' ? $base : 'usuario_' . $user->id;

                $counter = 1;
                while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $base . '_' . $counter;
                    $counter++;
                }
            }

            DB::table('users')->where('id', $user->id)->update([
                'email' => $email,
                'username' => $username,
            ]);
        }

        DB::table('personas')
            ->whereNotNull('email')
            ->get()
            ->each(function ($persona) {
                DB::table('personas')->where('id_persona', $persona->id_persona)->update([
                    'email' => Str::lower($persona->email),
                ]);
            });
    }

    public function down(): void
    {
    }
};
