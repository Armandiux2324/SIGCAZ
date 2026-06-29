<?php

namespace App\Http\Requests\Register;

use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin_type' => ['sometimes', 'in:national,state'],
            'state' => ['sometimes', 'string', 'max:255'],
            'municipality' => ['sometimes', 'string', 'max:255'],
            'group' => ['sometimes', 'string', 'max:255'],
            'attendance_type' => ['sometimes', 'in:alone,accompanied'],
            'accommodation_type' => ['sometimes', 'in:airbnb,hotel,own_home,family_or_friends'],
            'lodging' => ['nullable', 'string', 'max:255'],
            'stay_days' => ['sometimes', 'integer', 'min:1'],
            'transport_method' => ['sometimes', 'in:airplane,bus,car'],
            'folio_delivery_method' => ['sometimes', 'in:email,phone'],

            'participants' => ['sometimes', 'array', 'min:1'],
            'participants.*.id' => ['nullable', 'integer'],
            'participants.*.first_name' => ['required_with:participants', 'string', 'max:255'],
            'participants.*.last_name' => ['required_with:participants', 'string', 'max:255'],
            'participants.*.phone' => ['required_with:participants', 'string', 'max:20'],
            'participants.*.email' => ['required_with:participants', 'email', 'max:255'],
            'participants.*.gender' => ['required_with:participants', 'in:male,female'],
            'participants.*.shirt_size' => ['required_with:participants', 'string', 'max:10'],
            'participants.*.is_first_time' => ['required_with:participants', 'boolean'],
            'participants.*.participation_count' => ['nullable', 'integer', 'min:0', 'required_if:participants.*.is_first_time,false'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $participants = $this->input('participants');

            if (! $participants) return;
            

            $register = $this->route('register');
            $validIds = $register ? $register->participants()->pluck('id')->all() : [];

            foreach ($participants as $index => $participant) {
                $ownId = $participant['id'] ?? null;

                if ($ownId && ! in_array($ownId, $validIds)) {
                    $validator->errors()->add("participants.$index.id", 'Este participante no pertenece a este registro.');
                }

                if (! empty($participant['email'])) {
                    $taken = Participant::where('email', $participant['email'])
                        ->when($ownId, fn ($q) => $q->where('id', '!=', $ownId))
                        ->exists();

                    if ($taken) {
                        $validator->errors()->add("participants.$index.email", 'Este correo ya está registrado por otro participante.');
                    }
                }

                if (! empty($participant['phone'])) {
                    $taken = Participant::where('phone', $participant['phone'])->when($ownId, fn ($q) => $q->where('id', '!=', $ownId))->exists();

                    if ($taken) {
                        $validator->errors()->add("participants.$index.phone", 'Este teléfono ya está registrado por otro participante.');
                    }
                }
            }

            $attendanceType = $this->input('attendance_type', $register?->attendance_type);

            if ($attendanceType === 'alone' && count($participants) !== 1) {
                $validator->errors()->add('participants', 'Cuando el tipo de asistencia es solo, debe haber exactamente 1 participante.');
            }
        });
    }
}