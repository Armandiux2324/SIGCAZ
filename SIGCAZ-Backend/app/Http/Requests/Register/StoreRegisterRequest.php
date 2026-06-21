<?php

namespace App\Http\Requests\Register;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_type' => ['required','in:national,state'],
            'state' => ['required','string','max:255'],
            'municipality' => ['required','string','max:255'],
            'group' => ['required','string','max:255'],
            'is_first_time' => ['required','boolean'],
            'participation_count' => ['required','integer','min:0'],
            'attendance_type' => ['required','in:alone,accompanied'],
            'participant_count' => ['required','integer','min:1'],
            'accommodation_type' => ['required','in:airbnb,hotel,own_home,family_or_friends',],
            'lodging' => ['nullable','string','max:255'],
            'stay_days' => ['required','integer','min:1'],
            'folio_delivery_method' => ['required','in:email,phone',],
            'participants' => ['required','array','min:1'],
            'participants.*.first_name' => ['required','string','max:255'],
            'participants.*.last_name' => ['required','string','max:255'],
            'participants.*.phone' => ['required','string','max:20'],
            'participants.*.email' => ['required','email','max:255'],
            'participants.*.gender' => ['required','in:male,female',],
            'participants.*.shirt_size' => ['required','string','max:10'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $participants = $this->input('participants', []);

            if (count($participants) !== (int) $this->participant_count) {
                $validator->errors()->add(
                    'participant_count',
                    'El conteo de participantes no coincide con el número de participantes proporcionados.'
                );
            }

            if ($this->attendance_type === 'alone' && (int) $this->participant_count !== 1) {
                $validator->errors()->add(
                    'participant_count',
                    'Cuando el tipo de asistencia es solo, el conteo de participantes debe ser 1.'
                );
            }
        });
    }
}
