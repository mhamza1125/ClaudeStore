<?php

namespace App\Enums;

enum ImageViewType: string
{
    case Front = 'front';
    case Back = 'back';
    case Side = 'side';
    case Detail = 'detail';
    case Lifestyle = 'lifestyle';
    case Model = 'model';
    case Packaging = 'packaging';

    public function label(): string
    {
        return match ($this) {
            self::Front => 'Front view',
            self::Back => 'Back view',
            self::Side => 'Side view',
            self::Detail => 'Detail view',
            self::Lifestyle => 'Lifestyle photograph',
            self::Model => 'Model photograph',
            self::Packaging => 'Packaging photograph',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
