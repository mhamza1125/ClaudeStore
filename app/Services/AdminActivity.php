<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ContactMessage;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\SalesOrder;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Badges in the admin navigation: what customers have done since this staff member last
 * opened each section. Opening a section's list page clears its badge (only for that person);
 * Messages instead counts unread messages, so it clears as messages are read.
 */
class AdminActivity
{
    /** Navigation route => [ability needed to see it, badge tooltip suffix]. */
    public const SECTIONS = [
        'admin.quotes.index' => [null, 'new quote requests or customer replies'],
        'admin.orders.index' => ['orders.manage', 'new sales orders'],
        'admin.companies.index' => ['customers.view', 'new customer companies'],
        'admin.customer-accounts' => ['customers.view', 'new customer accounts'],
        'admin.messages' => ['customers.view', 'unread messages'],
    ];

    /** How far back badges look for someone who has never opened a section. */
    private const FIRST_VISIT_DAYS = 30;

    /** @return array<string, int> route name => count (only sections the user may see) */
    public function counts(User $user): array
    {
        $seen = DB::table('admin_section_views')->where('user_id', $user->id)->pluck('seen_at', 'section');
        $baseline = $user->created_at && $user->created_at->gt(now()->subDays(self::FIRST_VISIT_DAYS))
            ? $user->created_at : now()->subDays(self::FIRST_VISIT_DAYS);

        $counts = [];
        foreach (self::SECTIONS as $route => [$ability]) {
            if ($ability && ! $user->can($ability)) {
                continue;
            }
            $since = isset($seen[$route]) ? Carbon::parse($seen[$route]) : $baseline;
            $counts[$route] = $this->count($route, $since, $user);
        }

        return $counts;
    }

    public function markSeen(User $user, string $route): void
    {
        if (! array_key_exists($route, self::SECTIONS)) {
            return;
        }
        DB::table('admin_section_views')->upsert(
            [['user_id' => $user->id, 'section' => $route, 'seen_at' => now()]],
            ['user_id', 'section'],
            ['seen_at'],
        );
    }

    private function count(string $route, CarbonInterface $since, User $user): int
    {
        return match ($route) {
            // New requests, plus customers accepting or rejecting a quotation.
            'admin.quotes.index' => Quote::query()->visibleTo($user)
                ->where(fn ($q) => $q->where('created_at', '>', $since)
                    ->orWhereIn('id', QuoteActivity::query()->select('quote_id')->where('created_at', '>', $since)
                        ->where('type', 'status')->whereIn('meta->to', ['accepted', 'rejected'])
                        ->where(fn ($a) => $a->whereNull('user_id')->orWhere('user_id', '!=', $user->id))))
                ->count(),
            'admin.orders.index' => SalesOrder::query()->where('created_at', '>', $since)->count(),
            'admin.companies.index' => Company::query()->where('created_at', '>', $since)->count(),
            'admin.customer-accounts' => User::role('customer')->where('created_at', '>', $since)->count(),
            'admin.messages' => ContactMessage::query()->whereNull('read_at')->count(),
            default => 0,
        };
    }
}
