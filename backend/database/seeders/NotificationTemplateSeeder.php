<?php

namespace Database\Seeders;

use App\Services\Notification\TemplateSeedingService;
use Illuminate\Database\Seeder;

/**
 * Default notification templates (ar + en) from lang/{locale}/notifications.php. Idempotent: only missing ones are created.
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(TemplateSeedingService $templates): void
    {
        $templates->sync();
    }
}
