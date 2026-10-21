<?php

namespace App\Livewire;

use App\Services\QuoteBasketService;
use Livewire\Attributes\On;
use Livewire\Component;

class QuoteBasketCount extends Component
{
    #[On('basket-updated')]
    public function refresh(): void {}

    public function render(QuoteBasketService $basket)
    {
        return view('livewire.quote-basket-count', ['count' => $basket->count()]);
    }
}
