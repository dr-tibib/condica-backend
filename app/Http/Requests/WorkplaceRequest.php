<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkplaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:10', 'max:10000'],
            'timezone' => ['required', 'string', 'timezone'],
            'wifi_ssid' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'workplace name',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'radius' => 'geofence radius',
            'timezone' => 'timezone',
            'wifi_ssid' => 'WiFi SSID',
            'is_active' => 'active status',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Please provide a name for the workplace.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'radius.min' => 'Geofence radius must be at least 10 meters.',
            'radius.max' => 'Geofence radius cannot exceed 10 kilometers.',
            'timezone.timezone' => 'Please select a valid timezone.',
        ];
    }
}
