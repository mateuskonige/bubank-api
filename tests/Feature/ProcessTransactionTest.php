<?php

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Jobs\ProcessTransaction;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class ProcessTransactionTest extends TestCase
{
    public function test_deposit_transaction()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 0
        ]);
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'type' => TransactionType::DEPOSIT->value,
            'amount' => 10000,
            'status' => TransactionStatus::PENDING->value
        ]);

        // Act
        ProcessTransaction::dispatch($transaction);

        // Assert
        $this->assertEquals('completed', $transaction->fresh()->status);
        $this->assertEquals(10000, $account->fresh()->balance);
    }
}
