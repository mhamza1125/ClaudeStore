<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Quoted = 'quoted';
    case AwaitingResponse = 'awaiting_response';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under review',
            self::Quoted => 'Quoted',
            self::AwaitingResponse => 'Awaiting customer response',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
            self::Converted => 'Converted to order',
        };
    }

    /** Badge tone used by the design system. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending, self::UnderReview => 'neutral',
            self::Quoted, self::AwaitingResponse => 'accent',
            self::Accepted, self::Converted => 'success',
            self::Rejected, self::Expired => 'danger',
        };
    }

    /** Staff may still change prices, quantities and terms. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Pending, self::UnderReview, self::Quoted, self::AwaitingResponse, self::Expired], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
