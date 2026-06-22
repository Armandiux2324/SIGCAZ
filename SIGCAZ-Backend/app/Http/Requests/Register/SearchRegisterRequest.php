<?php

namespace App\Http\Requests\Register;

use Illuminate\Foundation\Http\FormRequest;

class SearchRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folio' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}