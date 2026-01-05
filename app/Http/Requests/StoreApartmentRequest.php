<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApartmentRequest extends FormRequest
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
            'city' => 'required|string|max:255',

            'town' => 'required|string|max:255',

            'space' => 'required|numeric|min:1',

            'rooms' => 'required|integer|min:1',

            'location' => [
                'nullable',
                'url',
                'regex:/^(https:\/\/)(maps\.app\.goo\.gl|goo\.gl\/maps|www\.google\.com\/maps|maps\.google\.com)/'
            ],

            'price_for_month' => 'required|numeric|min:0',

            'description' => 'required|string|min:10',

            'features' => 'required|string',

            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp',
        ];
    }
}
