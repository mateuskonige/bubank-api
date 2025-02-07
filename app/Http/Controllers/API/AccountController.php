<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\AccountService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\StoreUpdateAccount;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;

class AccountController extends Controller
{
    protected $service;

    public function __construct(AccountService $accountService)
    {
        $this->service = $accountService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = $this->service->get();

        return AccountResource::collection($accounts);
    }

    public function getTransactions(Account $account)
    {
        $data = $account->transactions()->get();

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\StoreAccountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAccountRequest $request)
    {
        $account = $this->service->create($request->validated());

        return new AccountResource($account);
    }

    /**
     * Display the specified resource.
     *
     * @param  Account  $account
     * @return \Illuminate\Http\Response
     */
    public function show(Account $account)
    {
        return new AccountResource($account);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\StoreUpdateAccount  $request
     * @param  string  $identify
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        $this->service->update($account, $request->validated());

        return response()->json(['message' => 'Account updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $identify
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $this->service->delete($account);

        return response()->json([], 204);
    }
}
