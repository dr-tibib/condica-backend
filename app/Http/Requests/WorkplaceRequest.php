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
            'name' => __('workplace name'),
            'latitude' => __('latitude'),
            'longitude' => __('longitude'),
            'radius' => __('geofence radius'),
            'timezone' => __('timezone'),
            'wifi_ssid' => __('WiFi SSID'),
            'is_active' => __('active status'),
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
            'name.required' => __('Please provide a name for the workplace.'),
            'latitude.between' => __('Latitude must be between -90 and 90.'),
            'longitude.between' => __('Longitude must be between -180 and 180.'),
            'radius.min' => __('Geofence radius must be at least 10 meters.'),
            'radius.max' => __('Geofence radius cannot exceed 10 kilometers.'),
            'timezone.timezone' => __('Please select a valid timezone.'),
        ];
    }
}
