<?php

namespace App\Jobs;

use App\Http\Services\CredentialNotificationService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCredentialNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private string $type,
        private string $secret
    ) {
        $this->onQueue('notifications');
    }

    public function handle(CredentialNotificationService $notifications): void
    {
        $user = User::query()->with('persona')->find($this->userId);

        if (! $user) {
            return;
        }

        if ($this->type === 'employee_welcome') {
            $notifications->sendEmployeeWelcome($user, $this->secret);
            return;
        }

        if ($this->type === 'recovery_code') {
            $notifications->sendRecoveryCode($user, $this->secret);
        }
    }
}
