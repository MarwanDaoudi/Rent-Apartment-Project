<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'phone'=>'required|digits:8|string|unique:temporary_users,phone',
            'password'=>'required|string|min:8',
            'first_name'=>'required|string|max:16',
            'last_name'=>'required|string|max:16',
            'profile_image'=>'required|image|max:4096',
            'id_image'=>'required|image|max:4096',
            'balance'=>'required|numeric|min:0',
            'role'=>'required|in:landlord,tenant',
            'birthday'=>'required|date|before:today|after:1990-01-01'
        ];
    }
}
