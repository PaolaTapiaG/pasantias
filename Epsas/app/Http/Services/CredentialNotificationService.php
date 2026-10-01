<?php

namespace App\Http\Services;

use App\Models\User;

class CredentialNotificationService
{
    public function __construct(
        private SmsGatewayService $smsGateway
    )
    {
    }

    public function sendEmployeeWelcome(User $user, string $temporaryPassword): array
    {
        $user->loadMissing('persona');

        $smsSent = false;
        if ($user->persona?->telefono) {
            $sms = $this->smsGateway->send(
                $user->persona->telefono,
                "Hola {$user->persona->nombre_completo}, tu contrasena temporal de EPSAS es {$temporaryPassword}.",
                'password_temporal',
                ['email' => $user->email, 'username' => $user->username],
                $user->persona->nombre_completo
            );

            $smsSent = $sms->status !== 'failed';
        }

        return [
            'sms' => $smsSent,
        ];
    }

    public function sendRecoveryCode(User $user, string $code): array
    {
        $user->loadMissing('persona');

        $smsSent = false;
        if ($user->persona?->telefono) {
            $sms = $this->smsGateway->send(
                $user->persona->telefono,
                "Tu codigo de recuperacion EPSAS es {$code}.",
                'codigo_recuperacion',
                ['email' => $user->email, 'username' => $user->username],
                $user->persona->nombre_completo
            );

            $smsSent = $sms->status !== 'failed';
        }

        return [
            'sms' => $smsSent,
        ];
    }
}
