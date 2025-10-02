<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'nameAr'   => 'nullable|string|max:255',
            'nameEn'   => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20|unique:users,phone,' . $this->user()->id,
            'language' => 'nullable|in:ar,en',
        ];
    }
}
