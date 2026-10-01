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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(): View
    {
        return view('perfil.admin', [
            'adminProfile' => Auth::user()?->loadMissing('persona'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user()->loadMissing('persona');
        $email = Str::lower(trim((string) $request->input('admin_email')));
        $emailRules = ['required', 'email', 'max:150'];

        if ($email !== Str::lower((string) $user->email)) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($user->id);
        }

        $passwordRequired = (bool) $user->must_change_password;
        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => $emailRules,
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'admin_description' => ['nullable', 'string', 'max:500'],
            'admin_photo' => ['nullable', 'image', 'max:2048'],
            'current_password' => [$passwordRequired ? 'required' : 'nullable', 'string'],
            'new_password' => [$passwordRequired ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ], $this->passwordValidationMessages());
        $data['admin_email'] = $email;

        $userUpdates = [
            'name' => $data['admin_name'],
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

        $persona = $this->resolvePersonaForUser($user, $data);

        if ($persona) {
            $photoPath = $persona->foto_path;
            [$nombres, $apellidos] = $this->splitFullName($data['admin_name']);

            if ($request->hasFile('admin_photo') && $request->file('admin_photo')->isValid()) {
                $photoPath = PrivateMedia::storeImage($request->file('admin_photo'), 'perfiles', 'perfil_admin', $photoPath);
            }

            $persona->update([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['admin_phone'] ?? $persona->telefono,
                'email' => $email,
                'foto_path' => $photoPath,
            ]);

            $persona->forceFill([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['admin_phone'] ?? $persona->telefono,
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
            : 'admin.perfil.index';

        return redirect()
            ->route($route)
            ->with('success', 'Perfil de administrador actualizado correctamente.');
    }

    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, ''];
        }

        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        $half = (int) ceil(count($parts) / 2);
        $nombres = implode(' ', array_slice($parts, 0, $half));
        $apellidos = implode(' ', array_slice($parts, $half));

        return [$nombres, $apellidos];
    }

    private function resolvePersonaForUser($user, array $data): ?Persona
    {
        if ($user->persona) {
            return $user->persona;
        }

        $emails = array_filter([
            Str::lower((string) $user->email),
            Str::lower((string) ($data['admin_email'] ?? '')),
        ]);

        if (empty($emails)) {
            return null;
        }

        $persona = Persona::query()
            ->whereIn('email', array_values(array_unique($emails)))
            ->orderBy('id_persona')
            ->first();

        if ($persona) {
            Log::info('[ADMIN PROFILE UPDATE] Persona linked automatically', [
                'user_id' => $user->id,
                'persona_id' => $persona->id_persona,
                'persona_email' => $persona->email,
            ]);
        }

        return $persona;
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
