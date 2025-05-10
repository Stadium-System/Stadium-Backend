<?php

namespace App\Http\Requests\Admin\Stadium;

use Illuminate\Foundation\Http\FormRequest;

class StoreStadiumRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'price_per_hour' => 'required|numeric|min:0',
            'capacity' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:open,closed',
            'user_id' => 'nullable|exists:users,id',
            'temp_upload_ids' => 'nullable|array',
            'temp_upload_ids.*' => 'integer|exists:temp_uploads,id',
        ];
    }
}
