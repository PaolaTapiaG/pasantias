<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class UserSessionSecurity
{
    public static function passwordRule(): Password
    {
        return Password::min(10)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols();
    }

    public static function invalidateOtherSessions(User $user, ?Request $request = null, ?string $plainPassword = null): void
    {
        if ($plainPassword && Auth::check() && Auth::id() === $user->getAuthIdentifier()) {
            Auth::logoutOtherDevices($plainPassword);
        }

        self::deleteDatabaseSessions($user, $request?->session()?->getId());
        $user->flushAuthCache();
        Cache::forget('auth:current-employee:'.$user->getKey());
    }

    private static function deleteDatabaseSessions(User $user, ?string $currentSessionId): void
    {
        $table = (string) config('session.table', 'sessions');

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return;
        }

        DB::table($table)
            ->where('user_id', $user->getAuthIdentifier())
            ->when($currentSessionId, fn ($query) => $query->where('id', '<>', $currentSessionId))
            ->delete();
    }
}
