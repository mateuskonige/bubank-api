<?php

namespace App\Http\Requests;

use App\Enums\TransactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            "type" => ["required"],
            "amount" => 'required|integer|min:1',
            "destination_account_id" => "nullable|string|required_if:type,transfer|exists:accounts,id",
        ];
    }
}
