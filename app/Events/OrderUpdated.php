<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ?string $branchId = null) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('owners')];

        if ($this->branchId !== null && $this->branchId !== '') {
            $channels[] = new PrivateChannel("branch.{$this->branchId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'OrderUpdated';
    }
}
