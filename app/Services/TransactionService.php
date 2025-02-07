<?php

namespace App\Services;

use App\Repositories\TransactionRepository;

class TransactionService
{
    protected $repository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->repository = $transactionRepository;
    }

    public function get()
    {
        return $this->repository->getAll();
    }

    public function create($data)
    {
        return $this->repository->create($data);
    }

    public function getById($model)
    {
        return $this->repository->getById($model->id);
    }

    public function getByUserId($model)
    {
        return $this->repository->getByUserId($model->user_id);
    }
}
