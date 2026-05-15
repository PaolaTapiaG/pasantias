<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        Log::info('[ADMIN PROFILE UPDATE] Incoming request', [
            'has_admin_photo' => $request->hasFile('admin_photo'),
            'content_type' => $request->header('Content-Type'),
        ]);

        $user = Auth::user()->loadMissing('persona');

        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'admin_description' => ['nullable', 'string', 'max:500'],
            'admin_photo' => ['nullable', 'image', 'max:2048'],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (!empty($data['new_password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contrasena actual no es valida.']);
            }

            $user->update(['password' => $data['new_password']]);
        }

        $persona = $this->resolvePersonaForUser($user, $data);

        if ($persona && (int) $user->id_persona !== (int) $persona->id_persona) {
            $user->update(['id_persona' => $persona->id_persona]);
            $user->refresh()->loadMissing('persona');
        }

        $user->update([
            'name' => $data['admin_name'],
            'email' => Str::lower($data['admin_email']),
        ]);

        if ($user->persona) {
            $photoPath = $user->persona->foto_path;
            [$nombres, $apellidos] = $this->splitFullName($data['admin_name']);

            if ($request->hasFile('admin_photo') && $request->file('admin_photo')->isValid()) {
                Log::info('[ADMIN PROFILE UPDATE] Admin photo detected', [
                    'original_name' => $request->file('admin_photo')->getClientOriginalName(),
                    'mime_type' => $request->file('admin_photo')->getMimeType(),
                    'size' => $request->file('admin_photo')->getSize(),
                ]);

                if ($photoPath && Str::startsWith($photoPath, 'storage/')) {
                    Storage::disk('public')->delete(Str::after($photoPath, 'storage/'));
                }

                $filename = 'perfil_admin_' . now()->format('YmdHis') . '_' . Str::random(8) . '.' . strtolower($request->file('admin_photo')->extension() ?: 'jpg');
                $stored = $request->file('admin_photo')->storeAs('perfiles', $filename, 'public');
                $photoPath = 'storage/' . $stored;

                Log::info('[ADMIN PROFILE UPDATE] Admin photo stored', [
                    'stored_relative_path' => $stored,
                    'public_path' => $photoPath,
                    'exists_on_disk' => $stored ? Storage::disk('public')->exists($stored) : false,
                ]);
            } elseif ($request->hasFile('admin_photo')) {
                Log::warning('[ADMIN PROFILE UPDATE] Admin photo file arrived but is invalid');
            }

            $user->persona->update([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $data['admin_phone'] ?? $user->persona->telefono,
                'email' => Str::lower($data['admin_email']),
                'foto_path' => $photoPath,
            ]);

            $user->forceFill([
                'name' => trim($user->persona->nombre_completo),
                'email' => Str::lower($user->persona->email),
            ])->save();
        }

        return redirect()
            ->route('admin.configuracion.index')
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
}
