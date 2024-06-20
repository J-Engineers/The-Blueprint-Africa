<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPassword extends FormRequest
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
            'otp_token' => 'required|integer',
            'password' => 'required|string|min:6|confirmed',
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
            'otp_token.required' => 'Verification Token is required!',
            'otp_token.integer' => 'Verification Token must be a number',
            'password.required' => 'Password is required!',
            'password.string' => 'Password must be a string',
            'password.min' => 'Password must be upto 6 characters',
            'password.confirmed' => 'Password must come with the confirmation field',
            'api_key.required' => 'API Key is required!',
            'api_key.string' => 'API Key must be a string',
        ];
    }
}
