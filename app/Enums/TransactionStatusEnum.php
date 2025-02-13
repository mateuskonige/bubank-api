<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Retorna uma descrição amigável para o status da transação.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::COMPLETED => 'Concluída',
            self::FAILED => 'Falha',
        };
    }
}
