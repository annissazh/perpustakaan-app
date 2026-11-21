<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Melihat daftar pengguna.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->role->canManageUsers();
    }

    /**
     * Membuat pengguna baru.
     */
    public function create(User $actor): bool
    {
        return $actor->role->canManageUsers();
    }

    /**
     * Mengubah akun pengguna lain.
     *
     * Superadmin dapat mengubah semua akun. Admin hanya dapat mengelola
     * petugas, anggota, dan profilnya sendiri.
     */
    public function update(User $actor, User $target): bool
    {
        if ($actor->role === Role::Superadmin) {
            return true;
        }

        if ($actor->role !== Role::Admin) {
            return false;
        }

        return $actor->id === $target->id
            || in_array($target->role, [Role::Petugas, Role::Anggota], true);
    }

    /**
     * Menghapus akun pengguna.
     */
    public function delete(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->role === Role::Superadmin) {
            return true;
        }

        return $actor->role === Role::Admin
            && in_array($target->role, [Role::Petugas, Role::Anggota], true);
    }

    /**
     * Role yang boleh ditetapkan oleh actor terhadap akun target.
     *
     * @return array<int, string>
     */
    public function assignableRoles(User $actor, ?User $target = null): array
    {
        if ($actor->role === Role::Superadmin) {
            return Role::values();
        }

        if ($actor->role === Role::Admin) {
            // Admin hanya boleh menetapkan role petugas atau anggota, kecuali
            // untuk profilnya sendiri yang tetap harus berupa admin.
            if ($target !== null && $target->id === $actor->id) {
                return [Role::Admin->value];
            }

            return [Role::Petugas->value, Role::Anggota->value];
        }

        return [];
    }
}
