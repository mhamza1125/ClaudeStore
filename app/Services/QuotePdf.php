<?php

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

/** Branded quotation PDF (always the light brand theme). */
class QuotePdf
{
    public function render(Quote $quote): string
    {
        $quote->loadMissing(['items', 'company']);

        return Pdf::loadView('pdf.quote', ['quote' => $quote])->setPaper('a4')->output();
    }

    public function download(Quote $quote)
    {
        return response($this->render($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quote->reference.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
