<?php

namespace App\Http\Requests\User\Event;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Stadium;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // User must have owner role
        if (!$this->user()->hasRole('owner')) {
            return false;
        }
        
        // User must own the stadium
        $stadium = Stadium::find($this->stadium_id);
        return $stadium && $stadium->user_id === $this->user()->id;
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
        ];
    }
}