<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteResponseRequest;
use App\Models\Quote;
use App\Quotes\QuotationDocument;
use App\Services\QuotePdf;
use App\Services\QuoteWorkflow;

/** Token-protected quotation page for buyers (no account needed). */
class CustomerQuoteController extends Controller
{
    public function __construct(private QuoteWorkflow $workflow) {}

    public function show(Quote $quote, string $token)
    {
        $this->authorizeToken($quote, $token);

        return view('store.quote-view', [
            'quote' => $quote->load('items'),
            'doc' => $this->sentDocument($quote),
            'acceptUrl' => route('quotes.public.accept', [$quote, $token]),
            'rejectUrl' => route('quotes.public.reject', [$quote, $token]),
            'pdfUrl' => route('quotes.public.pdf', [$quote, $token]),
            'layout' => 'store',
        ]);
    }

    public function pdf(Quote $quote, string $token, QuotePdf $pdf)
    {
        $this->authorizeToken($quote, $token);
        $doc = $this->sentDocument($quote);
        abort_if($doc === null, 404);

        return $pdf->download($doc);
    }

    public function accept(QuoteResponseRequest $request, Quote $quote, string $token)
    {
        $this->authorizeToken($quote, $token);
        $this->workflow->respond($quote, true, (int) $request->input('version'), $request->input('name'), null, $request->user());

        return back()->with('status', 'Thank you — the quotation has been accepted. We will confirm your order shortly.');
    }

    public function reject(QuoteResponseRequest $request, Quote $quote, string $token)
    {
        $this->authorizeToken($quote, $token);
        $this->workflow->respond($quote, false, (int) $request->input('version'), null, $request->input('reason'), $request->user());

        return back()->with('status', 'The quotation has been declined. Thank you for letting us know.');
    }

    /** Customers only ever see the snapshot of the last sent version (audit F-02). */
    private function sentDocument(Quote $quote): ?QuotationDocument
    {
        $version = $quote->sentVersion();

        return $version ? QuotationDocument::fromVersion($quote, $version) : null;
    }

    private function authorizeToken(Quote $quote, string $token): void
    {
        // Same 404 for unknown reference and wrong token: no information leak.
        abort_unless($this->workflow->tokenMatches($quote, $token), 404);
    }
}
