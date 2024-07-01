<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
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
            // http://localhost:8080/api/v1/wallet/payment/callback?trxref=os9gpjohww&reference=os9gpjohww
            'trxref' => 'required|string',
            'reference' => 'required|string',
            
        ];
    }
}
