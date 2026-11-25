<?php

namespace App\Services;

use App\Quotes\QuotationDocument;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Branded quotation PDF (always the light brand theme).
 *
 * Callers choose the document: customers get QuotationDocument::fromVersion (the sent
 * snapshot), staff get QuotationDocument::live (labelled draft when it has unsent changes).
 */
class QuotePdf
{
    public function render(QuotationDocument $doc): string
    {
        return Pdf::loadView('pdf.quote', ['doc' => $doc])->setPaper('a4')->output();
    }

    public function download(QuotationDocument $doc)
    {
        $suffix = $doc->isDraft ? '-draft' : '-v'.$doc->version;

        return response($this->render($doc), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$doc->reference.$suffix.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
