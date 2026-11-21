<?php

namespace App\Enums;

/**
 * Hak akses pengguna perpustakaan.
 *
 * Superadmin memiliki akses penuh (mengalahkan seluruh middleware role).
 */
enum Role: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Admin',
            self::Petugas => 'Petugas',
            self::Anggota => 'Anggota',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Superadmin, self::Admin => 'bg-light-danger text-danger',
            self::Petugas => 'bg-light-info text-info',
            self::Anggota => 'bg-light-success text-success',
        };
    }

    /**
     * Staff adalah role yang boleh mengakses menu operasional perpustakaan
     * (kategori, buku, dan sirkulasi).
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::Superadmin, self::Admin, self::Petugas], true);
    }

    /**
     * Role yang boleh mengelola akun pengguna.
     */
    public function canManageUsers(): bool
    {
        return in_array($this, [self::Superadmin, self::Admin], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
