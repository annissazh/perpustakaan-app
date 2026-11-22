<?php

namespace App\Enums;

/**
 * Metode pembayaran denda yang tersedia di perpustakaan.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Debit = 'debit';
    case EWallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Transfer => 'Transfer Bank',
            self::Debit => 'Kartu Debit',
            self::EWallet => 'E-Wallet',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'bi-cash-coin',
            self::Transfer => 'bi-arrow-left-right',
            self::Debit => 'bi-credit-card-2-front',
            self::EWallet => 'bi-phone',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
