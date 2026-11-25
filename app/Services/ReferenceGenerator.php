<?php

namespace App\Services;

use App\Models\ReferenceSequence;
use Illuminate\Support\Facades\DB;

/**
 * Sequential, per-year references such as LM-Q-2026-000123. The counter row is
 * locked for update so concurrent submissions cannot receive the same number.
 */
class ReferenceGenerator
{
    public function next(string $type): string
    {
        $year = (int) now()->format('Y');

        $value = DB::transaction(function () use ($type, $year) {
            $row = ReferenceSequence::query()->where('name', $type)->where('year', $year)->lockForUpdate()->first();
            if (! $row) {
                // First reference of the year: two requests may get here together. insertOrIgnore
                // lets exactly one create the row (unique name+year); both then lock it (audit F-28).
                DB::table('reference_sequences')->insertOrIgnore(['name' => $type, 'year' => $year, 'last_value' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $row = ReferenceSequence::query()->where('name', $type)->where('year', $year)->lockForUpdate()->firstOrFail();
            }
            $row->increment('last_value');

            return $row->last_value;
        });

        return sprintf('%s-%s-%d-%06d', config('brand.reference_prefix', 'LM'), $type, $year, $value);
    }
}
