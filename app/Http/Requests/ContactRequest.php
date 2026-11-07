<?php

namespace App\Http\Requests;

use App\Rules\TrustedEmail;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Contact Us form. Same bot heuristics as the quote form: a honeypot and a minimum fill time. */
class ContactRequest extends FormRequest
{
    public const MIN_FILL_SECONDS = 3;

    public const SUBJECTS = ['Product inquiry', 'Bulk / wholesale order', 'Custom & private label', 'Order or quotation support', 'Other'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', new TrustedEmail],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()\-.]{6,40}$/'],
            'company' => ['nullable', 'string', 'max:160'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'form_started' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number using digits, spaces, + ( ) - or . only.',
            'message.min' => 'Please tell us a little more (at least 10 characters).',
            'form_started.required' => 'Please reload the page and try again.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'your name', 'email' => 'your email', 'message' => 'your message'];
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
                $validator->errors()->add('form', 'That was quick! Please review your message and send it again.');
            }
        }];
    }

    public function isSpam(): bool
    {
        return filled($this->input('fax_number'));
    }
}
