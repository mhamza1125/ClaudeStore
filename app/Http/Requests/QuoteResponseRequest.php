<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $accepting = $this->routeIs('*.accept');

        return [
            'version' => ['required', 'integer', 'min:1'],
            'name' => [$accepting ? 'required' : 'nullable', 'string', 'max:120'],
            'confirm' => $accepting ? ['accepted'] : ['nullable'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm.accepted' => 'Please confirm that you accept the quotation and its terms.',
            'name.required' => 'Please enter your full name to accept.',
        ];
    }
}
