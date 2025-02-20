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
            "type" => ['required', Rule::enum(TransactionTypeEnum::class)],
            "amount" => ['required', 'integer', 'min:1'],
            "destination_account_id" => "nullable|string|exists:accounts,id",
        ];
    }

    // protected function withValidator($validator)
    // {
    //     $validator->sometimes('amount', 'max:' . auth()->user()->account->balance, function ($input) {
    //         // Aplica a regra "max" apenas se o tipo for "withdrawal" ou "transfer"
    //         return in_array($input->type, [TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::TRANSFER->value]);
    //     });
    // }
}
