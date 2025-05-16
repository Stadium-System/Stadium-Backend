<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|regex:/^2189\d{8}$/|size:12', Rule::unique('users', 'phone_number'),
            'password' => 'sometimes|nullable|string|min:8',
            'type' => 'nullable|string|in:admin,user,owner',
            'status' => 'nullable|string|in:active,inactive,banned',
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id'
        ];
    }
}
