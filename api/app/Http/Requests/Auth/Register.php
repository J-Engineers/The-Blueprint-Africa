<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Register extends FormRequest
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
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
            'user_name' => 'required|string',
            'phone' => 'required|string',
            'org_id' => 'required|string',
            'api_key' => [
                function ($attribute, $value, $fail)  {
                    if(!$value OR $value != env('API_KEY')){
                        $fail("Invalid API KEY");
                    }
                }
            ]
        ];
    }


    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.required' => 'Email is required!',
            'email.string' => 'Email must be a string',
            'email.email' => 'Email must be a valid email address',
            'password.required' => 'Password is required!',
            'password.string' => 'Password must be a string',
            'api_key.required' => 'API Key is required!',
            'api_key.string' => 'API Key must be a string',
            'org_id.required' => 'Organization Token is required!',
            'org_id.string' => 'Organization Token must be a string',
            'user_name.required' => 'Username is required!',
            'user_name.string' => 'Username must be a string',
            'phone.required' => 'Phone Number is required!',
            'phone.string' => 'Phone Number must be a string',
        ];
    }
}
