<?php

declare(strict_types=1);

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workplace_id' => ['required', 'integer', 'exists:workplaces,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'integer', 'min:0'],
            'method' => ['required', 'in:auto,manual'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workplace_id.required' => 'A workplace must be selected.',
            'workplace_id.exists' => 'The selected workplace is invalid.',
            'latitude.required' => 'Location latitude is required.',
            'latitude.between' => 'Location latitude must be between -90 and 90.',
            'longitude.required' => 'Location longitude is required.',
            'longitude.between' => 'Location longitude must be between -180 and 180.',
            'method.required' => 'Check-in method is required.',
            'method.in' => 'Check-in method must be either auto or manual.',
        ];
    }
}
