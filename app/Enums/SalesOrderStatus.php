<?php

namespace App\Enums;

enum SalesOrderStatus: string
{
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::InProduction => 'In production',
            self::Shipped => 'Shipped',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Confirmed, self::InProduction => 'accent',
            self::Shipped, self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
