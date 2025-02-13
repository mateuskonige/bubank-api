<?php

namespace App\Services;

use App\Models\Account;

class AccountService
{
    public function getAll()
    {
        return Account::all();
    }

    public function create(array $data)
    {
        return Account::create($data);
    }

    public function getById(string $id)
    {
        return Account::where('id', $id)->firstOrFail();
    }

    public function update(Account $account, array $data)
    {
        return $account->update($data);
    }

    public function destroy(Account $account)
    {
        if ($account->balance > 0) {
            return response()->json(['message' => 'Account balance is not zero.'], 400);
        }

        $account->delete();

        return response()->json(['message' => 'Account deleted successfully.'], 204);
    }

    public function restore(string $id)
    {
        $model = Account::withTrashed()->where('id', $id)->firstOrFail();

        $model->restore();

        return response()->json(['message' => 'Account restored successfully.'], 200);
    }
}
