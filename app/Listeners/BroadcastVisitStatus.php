<?php

namespace App\Listeners;

use App\Events\VisitStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastVisitStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(VisitStatusUpdated $event): void
    {
        //
    }
}
