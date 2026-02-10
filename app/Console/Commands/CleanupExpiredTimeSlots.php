<?php

// Create app/Console/Commands/CleanupExpiredTimeSlots.php

namespace App\Console\Commands;

use App\Services\TimeSlotBlockingService;
use Illuminate\Console\Command;

class CleanupExpiredTimeSlots extends Command
{
    protected $signature = 'timeslots:cleanup-expired';

    protected $description = 'Release expired temporarily blocked time slots and cleanup draft appointments';

    public function handle()
    {
        $timeSlotService = new TimeSlotBlockingService;
        $cleanedCount = $timeSlotService->cleanupExpiredSlots();

        $this->info("Cleaned up {$cleanedCount} expired time slots and draft appointments");

        return 0;
    }
}
