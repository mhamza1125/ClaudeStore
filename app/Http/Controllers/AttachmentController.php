<?php

namespace App\Http\Controllers;

use App\Models\QuoteAttachment;
use Illuminate\Support\Facades\Storage;

/** Quote attachments live on the private disk and are streamed only to authorised users. */
class AttachmentController extends Controller
{
    public function show(QuoteAttachment $attachment)
    {
        $this->authorize('view', $attachment->quote);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
