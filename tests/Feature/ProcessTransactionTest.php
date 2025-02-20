<?php

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Jobs\ProcessTransaction;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

class ProcessTransactionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_20_concurrent_transactions()
    {
        // Cria uma conta com saldo inicial de 100
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 100
        ]);

        // Cria 20 transações de depósito de 10 unidades cada
        $transactions = Transaction::factory()->count(20)->create([
            'account_id' => $account->id,
            'type' => TransactionTypeEnum::DEPOSIT->value,
            'amount' => 10,
            'status' => TransactionStatusEnum::PENDING->value,
        ]);

        // Dispara as Jobs de processamento de transações de forma síncrona (para simular concorrência)
        foreach ($transactions as $transaction) {
            ProcessTransaction::dispatchSync($transaction);
        }

        // Recupera a conta atualizada
        $updatedAccount = Account::find($account->id);

        // Verifica se o saldo final está correto
        $expectedBalance = 100 + (20 * 10); // Saldo inicial + 10 depósitos de 10
        $this->assertEquals($expectedBalance, $updatedAccount->balance);

        // Verifica se todas as transações foram marcadas como concluídas
        foreach ($transactions as $transaction) {
            $this->assertEquals(TransactionStatusEnum::COMPLETED->value, $transaction->fresh()->status);
        }
    }

    public function test_20_concurrent_withdrawals()
    {
        // Cria uma conta com saldo inicial de 200
        $account = Account::factory()->create(['balance' => 400]);

        // Cria 20 transações de saque de 20 unidades cada
        $transactions = Transaction::factory()->count(20)->create([
            'account_id' => $account->id,
            'type' => TransactionTypeEnum::WITHDRAWAL->value,
            'amount' => 20,
            'status' => TransactionStatusEnum::PENDING->value,
        ]);

        // Dispara as Jobs de processamento de transações de forma síncrona (para simular concorrência)
        foreach ($transactions as $transaction) {
            ProcessTransaction::dispatchSync($transaction);
        }

        // Recupera a conta atualizada
        $updatedAccount = Account::find($account->id);

        // Verifica se o saldo final está correto
        $expectedBalance = 400 - (20 * 20); // Saldo inicial - 10 saques de 20
        $this->assertEquals($expectedBalance, $updatedAccount->balance);

        // Verifica se todas as transações foram marcadas como concluídas
        foreach ($transactions as $transaction) {
            $this->assertEquals(TransactionStatusEnum::COMPLETED->value, $transaction->fresh()->status);
        }
    }

    public function test_20_concurrent_withdrawals_with_insufficient_balance()
    {
        // Cria uma conta com saldo inicial de 100
        $account = Account::factory()->create(['balance' => 200]);

        // Cria 20 transações de saque de 20 unidades cada
        $transactions = Transaction::factory()->count(20)->create([
            'account_id' => $account->id,
            'type' => TransactionTypeEnum::WITHDRAWAL->value,
            'amount' => 20,
            'status' => TransactionStatusEnum::PENDING->value,
        ]);

        // Dispara as Jobs de processamento de transações de forma síncrona
        foreach ($transactions as $transaction) {
            try {
                ProcessTransaction::dispatchSync($transaction);
            } catch (\Exception $e) {
                // Captura a exceção e verifica se é a esperada
                $this->assertEquals("Saldo insuficiente para saque.", $e->getMessage());
            }
        }

        // Recupera a conta e as transações atualizadas
        $updatedAccount = Account::find($account->id);
        $updatedTransactions = Transaction::whereIn('id', $transactions->pluck('id'))->get();

        // Verifica se o saldo final está correto
        // Apenas 10 saques de 20 devem ser processados (200 / 20 = 10)
        $expectedBalance = 200 - (10 * 20); // Saldo inicial - 10 saques de 20
        $this->assertEquals($expectedBalance, $updatedAccount->balance);

        // Verifica se 5 transações foram marcadas como falhas (saldo insuficiente)
        $failedTransactions = $updatedTransactions->where('status', TransactionStatusEnum::FAILED->value)->count();
        $this->assertEquals(10, $failedTransactions);

        // Verifica se 5 transações foram marcadas como concluídas
        $completedTransactions = $updatedTransactions->where('status', TransactionStatusEnum::COMPLETED->value)->count();
        $this->assertEquals(10, $completedTransactions);
    }
}
