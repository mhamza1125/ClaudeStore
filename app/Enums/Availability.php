<?php

namespace App\Enums;

enum Availability: string
{
    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case MadeToOrder = 'made_to_order';
    case OutOfStock = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In stock',
            self::LowStock => 'Low stock',
            self::MadeToOrder => 'Made to order',
            self::OutOfStock => 'Out of stock',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InStock => 'success',
            self::LowStock => 'warning',
            self::MadeToOrder => 'accent',
            self::OutOfStock => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
