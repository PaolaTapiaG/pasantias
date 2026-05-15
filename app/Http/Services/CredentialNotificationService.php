<?php

namespace App\Http\Services;

use App\Mail\EmployeeWelcomeMail;
use App\Mail\RecoveryCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CredentialNotificationService
{
    public function __construct(
        private SmsGatewayService $smsGateway,
        private RuntimeMailService $runtimeMailService
    )
    {
    }

    public function sendEmployeeWelcome(User $user, string $temporaryPassword): array
    {
        $user->loadMissing('persona');

        $smsSent = false;
        $emailSent = false;

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

        if ($user->email) {
            $emailSent = $this->sendEmail(
                $user->email,
                new EmployeeWelcomeMail($user, $temporaryPassword),
                'employee_welcome'
            );
        }

        return [
            'sms' => $smsSent,
            'email' => $emailSent,
        ];
    }

    public function sendRecoveryCode(User $user, string $code): array
    {
        $user->loadMissing('persona');

        $smsSent = false;
        $emailSent = false;

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

        if ($user->email) {
            $emailSent = $this->sendEmail(
                $user->email,
                new RecoveryCodeMail($user, $code),
                'recovery_code'
            );
        }

        return [
            'sms' => $smsSent,
            'email' => $emailSent,
        ];
    }

    private function sendEmail(string $recipient, object $mailable, string $type): bool
    {
        try {
            $this->runtimeMailService->send($recipient, $mailable);

            return true;
        } catch (\Throwable $exception) {
            Log::error('[MAIL DELIVERY ERROR]', [
                'type' => $type,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
