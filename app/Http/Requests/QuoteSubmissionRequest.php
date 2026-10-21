<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuoteSubmissionRequest extends FormRequest
{
    public const BUSINESS_TYPES = ['Retailer', 'Wholesaler / distributor', 'Brand / private label', 'Corporate gifting', 'E-commerce seller', 'Hospitality', 'Other'];

    /** Minimum seconds between rendering and submitting the form (bot heuristic). */
    public const MIN_FILL_SECONDS = 3;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9 ()\-.]{6,40}$/'],
            'country' => ['required', Rule::in(array_keys(countries()))],
            'delivery_destination' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'business_type' => ['nullable', Rule::in(self::BUSINESS_TYPES)],
            'expected_volume' => ['nullable', 'string', 'max:120'],
            'target_delivery_date' => ['nullable', 'date', 'after:today'],
            'preferred_currency' => ['nullable', Rule::in(config('store.display_currencies'))],
            'specifications' => ['nullable', 'string', 'max:5000'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            // mimes checks the detected MIME type of the content, not just the extension.
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
            'form_started' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number including the country code, e.g. +44 20 7946 0000.',
            'attachments.*.mimes' => 'Attachments must be PDF, JPG, PNG or WebP files.',
            'attachments.*.max' => 'Each attachment must be 10 MB or smaller.',
            'form_started.required' => 'Please reload the page and try again.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            try {
                $started = (int) decrypt((string) $this->input('form_started'));
            } catch (DecryptException) {
                $validator->errors()->add('form', 'Please reload the page and try again.');

                return;
            }
            if (now()->timestamp - $started < self::MIN_FILL_SECONDS) {
                $validator->errors()->add('form', 'That was quick! Please review your details and submit again.');
            }
        }];
    }

    public function isSpam(): bool
    {
        return filled($this->input('fax_number'));
    }
}
