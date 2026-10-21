<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\QuotePdf;

class QuotePdfController extends Controller
{
    public function show(Quote $quote, QuotePdf $pdf)
    {
        $this->authorize('view', $quote);

        return $pdf->download($quote);
    }
}
