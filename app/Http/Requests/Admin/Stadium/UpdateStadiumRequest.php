<?php

namespace App\Http\Requests\Admin\Stadium;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStadiumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow authenticated admin users to update orders.
        return $this->user() && $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'capacity' => 'sometimes|numeric|min:1',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:open,closed',
            'user_id' => 'sometimes|exists:users,id',
            'rating' => 'sometimes|numeric|between:0,5',
            'temp_upload_ids' => 'nullable|array',
            'temp_upload_ids.*' => 'integer|exists:temp_uploads,id',
        ];
    }
}
