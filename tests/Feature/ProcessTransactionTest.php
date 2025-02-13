<?php

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
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
            'type' => TransactionTypeEnum::DEPOSIT->value,
            'amount' => 10000,
            'status' => TransactionStatusEnum::PENDING->value
        ]);

        // Act
        ProcessTransaction::dispatch($transaction);

        // Assert
        $this->assertEquals('completed', $transaction->fresh()->status);
        $this->assertEquals(10000, $account->fresh()->balance);
    }

    public function test_withdrawal_transaction()
    {
        // Arrange
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 10000
        ]);
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'type' => TransactionTypeEnum::WITHDRAWAL->value,
            'amount' => 10000,
            'status' => TransactionStatusEnum::PENDING->value
        ]);

        // Act
        ProcessTransaction::dispatch($transaction);

        // Assert
        $this->assertEquals('completed', $transaction->fresh()->status);
        $this->assertEquals(0, $account->fresh()->balance);
    }

    public function test_transfer_transaction()
    {
        // Arrange
        $user = User::factory()->create();
        $receiver = User::factory()->create();

        $account_user = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 10000
        ]);
        $account_receiver = Account::factory()->create([
            'user_id' => $receiver->id,
            'balance' => 0
        ]);

        $transaction = Transaction::factory()->create([
            'account_id' => $account_user->id,
            'type' => TransactionTypeEnum::TRANSFER->value,
            'destination_account_id' => $account_receiver->id,
            'amount' => 10000,
            'status' => TransactionStatusEnum::PENDING->value
        ]);

        // Act
        ProcessTransaction::dispatch($transaction);

        // Assert
        $this->assertEquals('completed', $transaction->fresh()->status);
        $this->assertEquals(0, $account_user->fresh()->balance);
        $this->assertEquals(10000, $account_receiver->fresh()->balance);
    }
}
