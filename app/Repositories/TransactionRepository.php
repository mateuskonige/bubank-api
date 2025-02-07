<?php

namespace App\Repositories;

use App\Enums\TransactionStatus;
use App\Jobs\ProcessTransaction;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionRepository
{
    protected $model;

    public function __construct(Transaction $transaction)
    {
        $this->model = $transaction;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function create($data)
    {
        $account = Account::where('user_id', Auth::user()->id)->first();

        $transaction = Transaction::create([
            'account_id' => $account->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => TransactionStatus::PENDING->value
        ]);

        $response = ProcessTransaction::dispatch($transaction);

        return $response;
    }

    public function getById(string $id)
    {
        return $this->model->where('id', $id)->firstOrFail();
    }

    public function getByUserId(string $user_id)
    {
        return $this->model->where('user_id', $user_id)->get();
    }
}
