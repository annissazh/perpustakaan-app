<?php

namespace App\Enums;

/**
 * Status transaksi peminjaman.
 *
 * Catatan: nilai "overdue" hanya disimpan untuk data lama (hasil pengembalian
 * terlambat). Mulai dari versi ini, keterlambatan dihitung dari `due_date`
 * dan dicatat pada kolom `late_days`.
 */
enum BorrowingStatus: string
{
    case Borrowed = 'borrowed';
    case Returned = 'returned';
    case Overdue = 'overdue';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Borrowed => 'Dipinjam',
            self::Returned => 'Dikembalikan',
            self::Overdue => 'Terlambat',
            self::Lost => 'Hilang',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Borrowed => 'bg-light-warning text-warning',
            self::Returned => 'bg-light-success text-success',
            self::Overdue => 'bg-light-danger text-danger',
            self::Lost => 'bg-light-dark text-dark',
        };
    }

    /**
     * Status aktif: buku masih berada di tangan peminjam.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Borrowed, self::Overdue], true);
    }

    /**
     * Status final: buku sudah kembali atau dinyatakan hilang.
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::Returned, self::Lost], true);
    }
}
