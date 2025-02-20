<?php

namespace App\Jobs;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction;

    /**
     * Create a new job instance.
     *
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            // Bloqueia a conta para evitar condições de corrida
            $account = Account::where('id', $this->transaction->account_id)
                ->lockForUpdate()
                ->first();

            if (!$account) {
                throw new \Exception("Conta não encontrada.");
            }

            // Processa a transação com base no tipo
            switch ($this->transaction->type) {
                case TransactionTypeEnum::DEPOSIT->value:
                    $this->processDeposit($account);
                    break;
                case TransactionTypeEnum::WITHDRAWAL->value:
                    $this->processWithdrawal($account);
                    break;
                case TransactionTypeEnum::TRANSFER->value:
                    $this->processTransfer($account);
                    break;
                default:
                    throw new \Exception("Tipo de transação inválido.");
            }

            // Atualiza o status da transação para "concluído"
            $this->transaction->update(['status' => TransactionStatusEnum::COMPLETED->value]);

            Log::info("Transação {$this->transaction->id} processada com sucesso.");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Em caso de erro, atualiza o status da transação para "falha"
            $this->transaction->update(['status' => TransactionStatusEnum::FAILED->value]);

            Log::error("Erro ao processar transação {$this->transaction->id}: " . $e->getMessage());

            // Relança a exceção para que a job seja reprocessada (se configurado)
            throw $e;
        }
    }

    /**
     * Processa um depósito na conta.
     *
     * @param Account $account
     */
    protected function processDeposit(Account $account)
    {
        $account->balance += $this->transaction->amount;
        $account->save();
    }

    /**
     * Processa um saque na conta.
     *
     * @param Account $account
     */
    protected function processWithdrawal(Account $account)
    {
        if ((int) $account->balance < (int) $this->transaction->amount) {
            throw new \Exception("Saldo insuficiente para saque.");
        }

        // Verifica se o saque excede o limite para o horário
        if (now()->hour < 8 || now()->hour > 18) {
            if ($this->transaction->amount > 1_000_00) {
                throw new \Exception("Limite de saque excedido para o horário.");
            }
        } else {
            if ($this->transaction->amount > 10_000_00) {
                throw new \Exception("Limite de saque excedido.");
            }
        }

        $account->balance -= $this->transaction->amount;
        $account->save();
    }

    /**
     * Processa uma transferência entre contas.
     *
     * @param Account $sourceAccount
     */
    protected function processTransfer(Account $sourceAccount)
    {
        // Verifica se a conta de destino existe
        $destinationAccount = Account::where('id', $this->transaction->destination_account_id)
            ->lockForUpdate()
            ->first();

        if (!$destinationAccount) {
            throw new \Exception("Conta de destino não encontrada.");
        }

        // Verifica se a conta de origem tem saldo suficiente
        if ($sourceAccount->balance < $this->transaction->amount) {
            throw new \Exception("Saldo insuficiente para transferência.");
        }

        // // Verifica se o saque excede o limite para o horário
        // if (now()->hour < 8 || now()->hour > 17) {
        //     if ($this->transaction->amount > 1_000_00) {
        //         throw new \Exception("Limite de saque excedido para o horário.");
        //     }
        // } else {
        //     if ($this->transaction->amount > 10_000_00) {
        //         throw new \Exception("Limite de saque excedido.");
        //     }
        // }

        // Realiza a transferência
        $sourceAccount->balance -= $this->transaction->amount;
        $destinationAccount->balance += $this->transaction->amount;

        $sourceAccount->save();
        $destinationAccount->save();
    }
}
