<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The business calendar (config store.business_timezone, default Asia/Karachi).
 *
 * Dates such as a quotation's "valid until" or a negotiated price's validity window are
 * business dates: "valid until 10 March" means through 23:59:59 on 10 March in Sialkot.
 * Stored timestamps stay UTC (config app.timezone is not changed, owner decision D7).
 */
final class BusinessTime
{
    public static function zone(): string
    {
        return (string) config('store.business_timezone', 'Asia/Karachi');
    }

    /** Today's business date (Y-m-d), or the business date of $at. */
    public static function today(?CarbonInterface $at = null): string
    {
        return Carbon::instance($at ?? now())->setTimezone(self::zone())->toDateString();
    }

    /** The last instant of a business date. */
    public static function endOfDay(CarbonInterface|string $date): Carbon
    {
        $day = is_string($date) ? $date : $date->toDateString();

        return Carbon::parse($day, self::zone())->endOfDay();
    }
}
