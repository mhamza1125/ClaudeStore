<?php

use App\Services\QuoteWorkflow;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('quotes:expire', function (QuoteWorkflow $workflow) {
    $count = $workflow->expireOverdue();
    $this->info("{$count} quotation(s) marked as expired.");
})->purpose('Mark sent quotations past their validity date as expired');

Schedule::command('quotes:expire')->dailyAt('00:15');
Schedule::command('queue:prune-failed --hours=720')->daily();
