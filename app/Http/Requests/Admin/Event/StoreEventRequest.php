<?php

namespace App\Http\Requests\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow authenticated admin users to create events
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
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date|after:now',
            'stadium_id' => 'required|exists:stadia,id',
            'user_id' => 'required|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'integer|exists:media,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'date.after' => 'The event date must be in the future.',
            'stadium_id.exists' => 'The selected stadium does not exist.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}