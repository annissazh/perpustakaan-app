<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom `role` dibuat sebagai enum tanpa nilai "petugas", padahal role
     * tersebut dipakai di seluruh aplikasi (middleware, policy, dan form).
     * Enum perlu diubah menjadi kolom string agar seluruh role yang
     * didefinisikan pada App\Enums\Role bisa digunakan.
     *
     * Perubahan dilakukan dengan MODIFY COLUMN (bukan drop & add) supaya
     * nilai role yang sudah tersimpan tidak ikut hilang.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(sprintf(
                "ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(255) NOT NULL DEFAULT '%s'",
                Role::Anggota->value
            ));

            return;
        }

        // SQLite tidak mendukung MODIFY COLUMN sehingga kolom harus
        // dibuat ulang. Pada database pengujian datanya memang disposable.
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default(Role::Anggota->value)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan nilai role yang tidak valid ke default sebelum constrain.
        DB::table('users')
            ->whereNotIn('role', [
                Role::Superadmin->value,
                Role::Admin->value,
                Role::Anggota->value,
            ])
            ->update(['role' => Role::Anggota->value]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', [
                Role::Superadmin->value,
                Role::Admin->value,
                Role::Anggota->value,
            ])->default(Role::Anggota->value);
        });
    }
};
