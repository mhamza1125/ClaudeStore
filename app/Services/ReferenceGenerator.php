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
                $row = ReferenceSequence::query()->create(['name' => $type, 'year' => $year, 'last_value' => 0]);
                $row = ReferenceSequence::query()->whereKey($row->id)->lockForUpdate()->first();
            }
            $row->increment('last_value');

            return $row->last_value;
        });

        return sprintf('%s-%s-%d-%06d', config('brand.reference_prefix', 'LM'), $type, $year, $value);
    }
}
