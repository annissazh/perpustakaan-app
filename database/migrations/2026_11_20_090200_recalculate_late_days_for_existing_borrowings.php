<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menghitung ulang `late_days` untuk transaksi lama. Migrasi sebelumnya
     * salah menghitung selisih hari sehingga keterlambatan tercatat 0 hari
     * padahal buku tersebut dikembalikan melewati tenggat waktu.
     */
    public function up(): void
    {
        DB::table('borrowings')
            ->whereNotNull('return_date')
            ->whereNotNull('due_date')
            ->get(['id', 'return_date', 'due_date'])
            ->each(function (object $borrowing): void {
                $lateDays = max(0, (int) Carbon::parse($borrowing->due_date)
                    ->startOfDay()
                    ->diffInDays(Carbon::parse($borrowing->return_date)->startOfDay()));

                DB::table('borrowings')
                    ->where('id', $borrowing->id)
                    ->update(['late_days' => $lateDays]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('borrowings')->update(['late_days' => 0]);
    }
};
