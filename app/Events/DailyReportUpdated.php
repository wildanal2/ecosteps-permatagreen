<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailyReportUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $reportId,
        public string $status
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'daily-report-updated';
    }
}
