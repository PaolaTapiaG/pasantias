<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
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
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('success', 'Bienvenido ' . Auth::user()->name);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Las credenciales no coinciden con nuestros registros.');
    }

    public function showRecoveryRequest()
    {
        return view('auth.passwords.email');
    }

    public function sendRecoveryCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::with('persona')->where('email', $data['email'])->first();

        if (!$user || !$user->persona?->telefono) {
            return back()->withInput()->with('error', 'No se encontro un empleado con ese correo y telefono registrado.');
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->recoveryCacheKey($user->email), [
            'code' => Hash::make($code),
            'email' => $user->email,
        ], now()->addMinutes(10));

        Log::info('[SMS RECUPERACION] Codigo enviado', [
            'telefono' => $user->persona->telefono,
            'mensaje' => "Tu codigo de recuperacion EPSAS es {$code}.",
        ]);

        return redirect()
            ->route('password.reset.code', ['email' => $user->email])
            ->with('success', 'Se envio un codigo de recuperacion por SMS al telefono del empleado.')
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
            'password' => ['required', 'confirmed', 'min:8'],
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
}
