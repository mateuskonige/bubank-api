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
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    protected $service;

    public function __construct(AccountService $accountService)
    {
        $this->service = $accountService;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        if (!Gate::allows('delete', $account)) {
            abort(403);
        }

        $response = $this->service->destroy($account);

        return $response;
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        $response = $this->service->restore($id);

        return $response;
    }
}
