<?php

namespace App\Services;

use App\Repositories\AccountRepository;

class AccountService
{
    protected $repository;

    public function __construct(AccountRepository $accountRepository)
    {
        $this->repository = $accountRepository;
    }

    public function get()
    {
        return $this->repository->getAll();
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function getById($model)
    {
        return $this->repository->getById($model->id);
    }

    public function update($model, array $data)
    {
        return $this->repository->updateById($model->id, $data);
    }

    public function delete($model)
    {
        return $this->repository->deleteById($model->id);
    }
}
