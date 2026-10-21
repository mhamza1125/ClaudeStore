<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /** Record a sensitive administrative change. Never pass secrets in $changes. */
    public static function log(string $action, ?Model $subject = null, ?string $description = null, array $changes = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    /** Extract old/new pairs for dirty attributes of a model about to be saved. */
    public static function diff(Model $model, array $only = []): array
    {
        $changes = [];
        foreach ($model->getDirty() as $key => $new) {
            if ($only && ! in_array($key, $only, true)) {
                continue;
            }
            $changes[$key] = ['from' => $model->getOriginal($key), 'to' => $new];
        }

        return $changes;
    }
}
