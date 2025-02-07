<?php

namespace App\Enums;

enum AccountType: string
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';

    /**
     * Retorna uma descrição amigável para o tipo de conta.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::CHECKING => 'Conta Corrente',
            self::SAVINGS => 'Conta Poupança',
        };
    }
}
