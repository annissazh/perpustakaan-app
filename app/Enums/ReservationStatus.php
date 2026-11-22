<?php

namespace App\Enums;

/**
 * Siklus hidup reservasi buku.
 *
 * Pending  : anggota menunggu buku tersedia.
 * Fulfilled: reservasi sudah dikonversi menjadi peminjaman.
 * Cancelled: reservasi dibatalkan (oleh anggota atau petugas).
 * Expired  : reservasi kadaluwarsa karena buku belum diambil.
 */
enum ReservationStatus: string
{
    case Pending = 'pending';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Fulfilled => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-light-warning text-warning',
            self::Fulfilled => 'bg-light-success text-success',
            self::Cancelled => 'bg-light-secondary text-secondary',
            self::Expired => 'bg-light-danger text-danger',
        };
    }

    /**
     * Status reservasi yang masih berjalan (buku belum diterima anggota).
     */
    public function isActive(): bool
    {
        return $this === self::Pending;
    }
}
