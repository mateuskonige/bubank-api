<?php

namespace App\Services;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Jobs\ProcessTransaction;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    public function get()
    {
        $transactionsReceived = Transaction::where('destination_account_id', Auth::user()->account->id)->get();
        $transactionsSent = Transaction::where('account_id', Auth::user()->account->id)->get();

        $transactions = $transactionsReceived->merge($transactionsSent);

        $transactions = $transactions->sortByDesc('created_at')->values();

        return $transactions;
    }

    public function create($data)
    {
        $account = Account::where('user_id', Auth::user()->id)->first();

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => TransactionStatusEnum::PENDING->value,
            'destination_account_id' => $data['destination_account_id']
        ]);

        ProcessTransaction::dispatch($transaction);

        return $transaction;
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
