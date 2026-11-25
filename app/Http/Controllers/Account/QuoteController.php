<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteResponseRequest;
use App\Models\Quote;
use App\Quotes\QuotationDocument;
use App\Services\QuotePdf;
use App\Services\QuoteWorkflow;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::query()->visibleTo(auth()->user())->withCount('items')->latest('id')->paginate(15);

        return view('account.quotes', compact('quotes'));
    }

    public function show(Quote $quote)
    {
        $this->authorize('view', $quote);
        $canRespond = auth()->user()->can('respond', $quote);
        $version = $quote->sentVersion();

        return view('store.quote-view', [
            'quote' => $quote->load('items'),
            // Customer-facing figures come only from the sent snapshot (audit F-02).
            'doc' => $version ? QuotationDocument::fromVersion($quote, $version) : null,
            'acceptUrl' => $canRespond ? route('account.quotes.accept', $quote) : null,
            'rejectUrl' => $canRespond ? route('account.quotes.reject', $quote) : null,
            'pdfUrl' => route('account.quotes.pdf', $quote),
            'layout' => 'account',
            'canRespond' => $canRespond,
        ]);
    }

    public function pdf(Quote $quote, QuotePdf $pdf)
    {
        $this->authorize('view', $quote);
        $version = $quote->sentVersion();
        abort_if($version === null, 404);

        return $pdf->download(QuotationDocument::fromVersion($quote, $version));
    }

    public function accept(QuoteResponseRequest $request, Quote $quote, QuoteWorkflow $workflow)
    {
        $this->authorize('respond', $quote);
        $workflow->respond($quote, true, (int) $request->input('version'), $request->input('name'), null, $request->user());

        return back()->with('status', 'Quotation accepted. We will confirm your sales order shortly.');
    }

    public function reject(QuoteResponseRequest $request, Quote $quote, QuoteWorkflow $workflow)
    {
        $this->authorize('respond', $quote);
        $workflow->respond($quote, false, (int) $request->input('version'), null, $request->input('reason'), $request->user());

        return back()->with('status', 'Quotation declined.');
    }
}
