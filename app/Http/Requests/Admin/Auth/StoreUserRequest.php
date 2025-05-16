<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'phone_number' => ['required', 'string', 'regex:/^2189\d{8}$/'], Rule::unique('users', 'phone_number'),
            'password' => 'required|string|min:8',
            'type' => 'required|string|in:admin,user,owner',
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id'
        ];
    }
}
