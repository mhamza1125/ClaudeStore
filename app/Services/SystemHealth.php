<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Is background processing running? (audit F-06) On shared hosting the queue worker and the
 * scheduler run only from cron (docs/HOSTINGER.md); if cron stops, quotation emails, image
 * optimisation and expiry silently stop too. Shown to administrators in the admin layout.
 */
class SystemHealth
{
    public const HEARTBEAT_KEY = 'scheduler.heartbeat';

    public const MAX_AGE_SECONDS = 300;

    /** Written every minute by a scheduled task (routes/console.php). */
    public static function beat(): void
    {
        Cache::forever(self::HEARTBEAT_KEY, now()->getTimestamp());
    }

    /**
     * @return array<string, string> key => message (empty when healthy)
     */
    public function warnings(): array
    {
        $warnings = [];
        $now = now()->getTimestamp();

        try {
            if (config('queue.default') === 'database') {
                $oldest = DB::table(config('queue.connections.database.table', 'jobs'))
                    ->whereNull('reserved_at')->where('available_at', '<=', $now)->min('available_at');
                if ($oldest !== null && $now - (int) $oldest > self::MAX_AGE_SECONDS) {
                    $warnings['queue'] = 'Emails and image processing are waiting: the oldest queued job is '.intdiv($now - (int) $oldest, 60).' minutes old. Check that the queue worker cron job is running.';
                }
            }

            $failed = DB::table(config('queue.failed.table', 'failed_jobs'))->count();
            if ($failed > 0) {
                $warnings['failed'] = "{$failed} background job(s) failed (for example an email that could not be sent). Run php artisan queue:failed on the server to review them.";
            }
        } catch (Throwable) {
            // A missing table must never break the admin; the scheduler check below still runs.
        }

        $beat = Cache::get(self::HEARTBEAT_KEY);
        if ($beat === null) {
            $warnings['scheduler'] = 'The scheduler has not run yet: quotation expiry and maintenance tasks are not happening. Add the schedule:run cron job.';
        } elseif ($now - (int) $beat > self::MAX_AGE_SECONDS) {
            $warnings['scheduler'] = 'The scheduler last ran '.intdiv($now - (int) $beat, 60).' minutes ago. Check the schedule:run cron job.';
        }

        return $warnings;
    }
}
