<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Batasan unique lama melarang anggota memesan ulang buku yang
        // pernah direservasi. Aturan baru: hanya satu reservasi AKTIF per
        // buku (dicek di level aplikasi + validasi request).
        Schema::table('reservations', function (Blueprint $table): void {
            // MySQL tidak bisa drop index yang dipakai foreign key,
            // sehingga foreign key dilepas lebih dulu.
            $table->dropForeign(['user_id']);
            $table->dropForeign(['book_id']);
            $table->dropUnique(['user_id', 'book_id']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['book_id']);
            $table->unique(['user_id', 'book_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }
};
