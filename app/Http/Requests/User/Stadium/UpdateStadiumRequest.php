<?php

namespace App\Http\Requests\User\Stadium;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStadiumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $stadium = $this->route('stadium');
        return $this->user()->hasRole('owner') && $this->user()->id === $stadium->user_id;
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'price_per_hour' => 'sometimes|numeric|min:0',
            'capacity' => 'sometimes|numeric|min:1',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:open,closed',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'integer|exists:media,id'
        ];
    }
}
