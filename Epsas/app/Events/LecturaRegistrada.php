<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LecturaRegistrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $lectura)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('lecturas')];
    }

    public function broadcastAs(): string
    {
        return 'lectura.registrada';
    }

    public function broadcastWith(): array
    {
        return $this->lectura;
    }
}