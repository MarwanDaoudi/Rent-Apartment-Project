<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApartmentRequest extends FormRequest
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
            'city' => 'sometimes|string|max:255',
            'town' => 'sometimes|string|max:255',
            'space' => 'sometimes|numeric|min:1',
            'rooms' => 'sometimes|integer|min:1',
            'description' => 'sometimes|string|min:10',
            'features' => 'sometimes|string',
            'price_for_month' => 'sometimes|numeric|min:0',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
            'deleted_images' => 'sometimes|array',
            'deleted_images.*' => 'exists:images,id',
        ];
    }
}
