<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'discount_bps', 'is_default'];

    protected function casts(): array
    {
        return ['discount_bps' => 'integer', 'is_default' => 'boolean'];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
