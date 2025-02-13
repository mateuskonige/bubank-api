<?php

namespace App\Services;

use App\Enums\TransactionStatusEnum;
use App\Jobs\ProcessTransaction;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function get()
    {
        return Account::all();
    }

    public function create($data)
    {
        $account = Account::where('user_id', Auth::user()->id)->first();

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => TransactionStatusEnum::PENDING->value
        ]);

        $response = ProcessTransaction::dispatch($transaction);

        return $response;
    }

    public function getById(string $id)
    {
        return Account::where('id', $id)->firstOrFail();
    }

    public function getByUserId(string $user_id)
    {
        return Account::where('user_id', $user_id)->get();
    }
}
