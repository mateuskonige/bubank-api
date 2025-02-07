<?php

namespace App\Repositories;

use App\Models\Account;

class AccountRepository
{
    protected $model;

    public function __construct(Account $account)
    {
        $this->model = $account;
    }

    public function getAll()
    {
        return $this->model->all();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function getById(string $id)
    {
        return $this->model->where('id', $id)->firstOrFail();
    }

    public function updateById(string $id, array $data)
    {
        $account = $this->getById($id);

        return $account->update($data);
    }

    public function deleteById(string $id)
    {
        $account = $this->getById($id);

        return $account->delete();
    }
}
