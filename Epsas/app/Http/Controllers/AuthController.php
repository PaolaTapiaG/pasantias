<?php

namespace App\Http\Controllers;

use App\Http\Services\CredentialNotificationService;
use App\Jobs\SendCredentialNotification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\OperationalCache;
use App\Support\UserSessionSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private CredentialNotificationService $credentialNotifications)
    {
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $login = trim((string) $credentials['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $loginValue = in_array($field, ['email', 'username'], true) ? mb_strtolower($login) : $login;

        if (Auth::attempt([$field => $loginValue, 'password' => $credentials['password']], false)) {
            $request->session()->regenerate();
            $user = Auth::user()->loadMissing('persona');
            $roles = $user->cachedRoleNames();

            Cache::put($this->authUserCacheKey($user), $user, now()->addMinutes((int) config('auth.user_cache_minutes', 1440)));
            $this->warmSharedSettings();
            $this->deferRoleWarmup($roles);

            return redirect()->route('dashboard')
                ->with('success', 'Bienvenido ' . $user->name);
        }

        return back()
            ->withInput($request->only('login'))
            ->with('error', 'Las credenciales no coinciden con nuestros registros.');
    }

    public function showRecoveryRequest()
    {
        return view('auth.passwords.email');
    }

    public function sendRecoveryCode(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = trim((string) $data['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::with('persona')->where($field, mb_strtolower($login))->first();

        if (!$user || (!$user->persona?->telefono && !$user->email)) {
            return back()
                ->withInput()
                ->with('success', 'Si la cuenta existe y tiene un contacto valido, recibira un codigo de recuperacion.');
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->recoveryCacheKey($user->email), [
            'code' => Hash::make($code),
            'email' => $user->email,
        ], now()->addMinutes(10));

        $this->sendRecoveryCodeAfterResponse((int) $user->id, $code);

        return redirect()
            ->route('password.reset.code', ['email' => $user->email])
            ->with('success', 'Si la cuenta existe y tiene un contacto valido, recibira un codigo de recuperacion.')
            ->with('sms_debug_code', app()->isLocal() ? $code : null);
    }

    public function showRecoveryReset(Request $request)
    {
        return view('auth.passwords.reset', [
            'email' => (string) $request->query('email', old('email')),
        ]);
    }

    public function resetWithRecoveryCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'codigo' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $payload = Cache::get($this->recoveryCacheKey($data['email']));
        if (!$payload || !Hash::check($data['codigo'], $payload['code'])) {
            return back()->withInput($request->only('email'))->with('error', 'El codigo de recuperacion no es valido o ya vencio.');
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return back()->withInput($request->only('email'))->with('error', 'No se encontro el usuario para restablecer la contrasena.');
        }

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        UserSessionSecurity::invalidateOtherSessions($user, null);
        Cache::forget($this->recoveryCacheKey($data['email']));

        return redirect()->route('login')->with('success', 'Contrasena restablecida correctamente. Ya puedes iniciar sesion.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Sesion cerrada correctamente.');
    }

    private function recoveryCacheKey(string $email): string
    {
        return 'auth:recovery:' . sha1(strtolower($email));
    }

    private function sendRecoveryCodeAfterResponse(int $userId, string $code): void
    {
        $connection = $this->credentialQueueConnection();

        if ($connection) {
            SendCredentialNotification::dispatch($userId, 'recovery_code', $code)
                ->onConnection($connection);

            return;
        }

        app()->terminating(function () use ($userId, $code) {
            try {
                $user = User::query()->with('persona')->find($userId);

                if ($user) {
                    $this->credentialNotifications->sendRecoveryCode($user, $code);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    private function credentialQueueConnection(): ?string
    {
        $default = (string) config('queue.default', 'sync');

        if ($default !== 'sync') {
            return $default;
        }

        return Cache::remember('queue.jobs-table-available', now()->addMinutes(30), fn () => Schema::hasTable('jobs'))
            ? 'database'
            : null;
    }

    private function authUserCacheKey(User $user): string
    {
        return 'auth:user:' . str_replace('\\', '.', $user::class) . ':' . $user->getAuthIdentifier();
    }

    private function warmSharedSettings(): void
    {
        OperationalCache::rememberDomain('settings', 'shared.company-settings', fn () => SystemSetting::getValue('general', [
            'company_name' => 'EPSAS',
            'company_alias' => 'Panel administrativo',
            'company_logo' => null,
        ]));
    }

    private function deferRoleWarmup($roles): void
    {
        if (app()->runningInConsole() || ! config('app.login_warmup_enabled')) {
            return;
        }

        app()->terminating(function () use ($roles) {
            try {
                app(DashboardController::class)->warmForRoles($roles);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }
}
