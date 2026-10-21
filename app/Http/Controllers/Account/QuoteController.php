<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteResponseRequest;
use App\Models\Quote;
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

        return view('store.quote-view', [
            'quote' => $quote->load('items'),
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
        abort_if($quote->version === 0, 404);

        return $pdf->download($quote);
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
