<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Support\PrivateMedia;
use App\Support\UserSessionSecurity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SecretariaProfileController extends Controller
{
    public function edit(): View
    {
        return view('secretaria.perfil', [
            'user' => Auth::user()->loadMissing('persona'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('persona');
        $email = Str::lower(trim((string) $request->input('email')));
        $emailRules = ['required', 'email', 'max:150'];

        if ($email !== Str::lower((string) $user->email)) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($user->id);
        }

        $passwordRequired = (bool) $user->must_change_password;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'current_password' => [$passwordRequired ? 'required' : 'nullable', 'string'],
            'new_password' => [$passwordRequired ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ], $this->passwordValidationMessages());

        $userUpdates = [
            'name' => $data['name'],
            'email' => $email,
        ];
        $passwordChanged = !empty($data['new_password']);

        if ($passwordChanged) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contrasena actual no es valida.']);
            }

            $userUpdates['password'] = $data['new_password'];
            $userUpdates['must_change_password'] = false;
        }

        $persona = $user->persona ?? Persona::query()
            ->where('email', $email)
            ->orderBy('id_persona')
            ->first();

        if ($persona) {
            [$nombres, $apellidos] = $this->splitName($data['name']);
            $photoPath = $persona->foto_path;

            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photoPath = PrivateMedia::storeImage($request->file('photo'), 'perfiles', 'perfil_secretaria', $photoPath);
            }

            $persona->update([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['phone'] ?? $persona->telefono,
                'email' => $email,
                'foto_path' => $photoPath,
            ]);

            $persona->forceFill([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['phone'] ?? $persona->telefono,
                'email' => $email,
                'foto_path' => $photoPath,
            ]);

            $userUpdates['name'] = trim($nombres . ' ' . $apellidos);
            $userUpdates['email'] = $email;
            $userUpdates['id_persona'] = $persona->id_persona;
            $user->setRelation('persona', $persona);
        }

        $user->forceFill($userUpdates)->save();
        if ($passwordChanged) {
            UserSessionSecurity::invalidateOtherSessions($user, $request, $data['new_password']);
        }

        $user->flushAuthCache();
        Cache::forget('auth:current-employee:'.$user->getKey());
        Cache::put($this->authUserCacheKey($user), $user, now()->addMinutes((int) config('auth.user_cache_minutes', 1440)));

        $route = $passwordRequired && ! ($user->must_change_password)
            ? 'dashboard'
            : 'secretaria.perfil.index';

        return redirect()->route($route)->with('success', 'Perfil de secretaria actualizado correctamente.');
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, ''];
        }

        $half = (int) ceil(count($parts) / 2);

        return [
            implode(' ', array_slice($parts, 0, $half)),
            implode(' ', array_slice($parts, $half)),
        ];
    }

    private function authUserCacheKey($user): string
    {
        return 'auth:user:' . str_replace('\\', '.', $user::class) . ':' . $user->getAuthIdentifier();
    }

    private function passwordValidationMessages(): array
    {
        return [
            'current_password.required' => 'Debes escribir tu contrasena actual para cambiar la contrasena temporal.',
            'new_password.required' => 'Debes escribir una nueva contrasena para desbloquear el acceso.',
            'new_password.confirmed' => 'La nueva contrasena y la confirmacion deben ser iguales.',
        ];
    }
}
