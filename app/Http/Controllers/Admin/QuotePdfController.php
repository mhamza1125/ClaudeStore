<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Quotes\QuotationDocument;
use App\Services\QuotePdf;

class QuotePdfController extends Controller
{
    /** Staff PDF: the live state, labelled "Draft" while it has unsent changes or was never sent. */
    public function show(Quote $quote, QuotePdf $pdf)
    {
        $this->authorize('view', $quote);

        return $pdf->download(QuotationDocument::live($quote));
    }
}
