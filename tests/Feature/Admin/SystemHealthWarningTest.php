<?php

namespace Tests\Feature\Admin;

use App\Services\SystemHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Audit F-06: administrators see a warning when the queue or the scheduler is not running
 * (shared hosting has no Supervisor; both depend on cron). Sales reps never see it.
 */
class SystemHealthWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']); // production setting; tests otherwise run the queue in-process
        Cache::forever(SystemHealth::HEARTBEAT_KEY, now()->getTimestamp()); // healthy scheduler unless a test says otherwise
    }

    private function pendingJob(int $minutesOld): void
    {
        $at = now()->subMinutes($minutesOld)->getTimestamp();
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => $at, 'created_at' => $at]);
    }

    public function test_no_warning_when_everything_runs(): void
    {
        $this->pendingJob(1);
        $this->actingAs($this->staff('admin'))->get('/admin')->assertOk()->assertDontSee('data-health-warning', false);
    }

    public function test_warns_when_the_oldest_pending_job_is_older_than_five_minutes(): void
    {
        $this->pendingJob(6);
        $this->actingAs($this->staff('admin'))->get('/admin')->assertOk()
            ->assertSee('data-health-warning="queue"', false)->assertSee('queue worker');
    }

    public function test_warns_when_the_scheduler_heartbeat_is_missing_or_stale(): void
    {
        Cache::forget(SystemHealth::HEARTBEAT_KEY);
        $this->actingAs($this->staff('super-admin'))->get('/admin')->assertSee('data-health-warning="scheduler"', false);

        Cache::forever(SystemHealth::HEARTBEAT_KEY, now()->subMinutes(6)->getTimestamp());
        $this->get('/admin')->assertSee('data-health-warning="scheduler"', false);
    }

    public function test_warns_when_failed_jobs_exist(): void
    {
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);
        $this->actingAs($this->staff('admin'))->get('/admin')->assertSee('data-health-warning="failed"', false);
    }

    public function test_sales_reps_never_see_the_warning(): void
    {
        $this->pendingJob(30);
        Cache::forget(SystemHealth::HEARTBEAT_KEY);
        $this->actingAs($this->staff('sales-rep'))->get('/admin')->assertOk()->assertDontSee('data-health-warning', false);
    }

    public function test_the_scheduler_writes_its_heartbeat_every_minute(): void
    {
        Cache::forget(SystemHealth::HEARTBEAT_KEY);
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'scheduler-heartbeat');

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
        $event->run($this->app);
        $this->assertGreaterThan(now()->subMinute()->getTimestamp(), (int) Cache::get(SystemHealth::HEARTBEAT_KEY));
    }

    public function test_queue_retry_after_exceeds_every_job_timeout(): void
    {
        $longest = 60; // the worker's default timeout for jobs that set none (mail)
        foreach (glob(app_path('Jobs/*.php')) as $file) {
            $class = 'App\\Jobs\\'.basename($file, '.php');
            $timeout = (new \ReflectionClass($class))->getDefaultProperties()['timeout'] ?? null;
            $longest = max($longest, (int) $timeout);
        }
        $this->assertGreaterThan($longest, config('queue.connections.database.retry_after'), 'a running job must not be handed to a second worker');
    }
}
