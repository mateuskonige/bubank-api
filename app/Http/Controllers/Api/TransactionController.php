<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\TransactionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\StoreUpdateTransaction;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;

class TransactionController extends Controller
{
    protected $service;

    public function __construct(TransactionService $transactionService)
    {
        $this->service = $transactionService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transactions = $this->service->get();

        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\StoreTransactionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTransactionRequest $request)
    {
        $transaction = $this->service->create($request->validated());

        return response()->json($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction);
    }

    /**
     * Get the status of a transaction
     */
    public function status($id)
    {
        $transaction = Transaction::findOrFail($id);
        return response()->json([
            'status' => $transaction->status,
            'message' => $transaction->error_message ?? null,
        ]);
    }
}
