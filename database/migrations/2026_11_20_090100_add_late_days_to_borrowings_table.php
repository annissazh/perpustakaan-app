<?php

use App\Enums\BorrowingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->integer('late_days')->default(0)->after('duration_days');
        });

        // Normalisasi data lama: status "overdue" yang sudah memiliki return_date
        // berarti buku dikembalikan terlambat, bukan sedang dipinjam.
        DB::table('borrowings')
            ->where('status', BorrowingStatus::Overdue->value)
            ->whereNotNull('return_date')
            ->get(['id', 'return_date', 'due_date'])
            ->each(function (object $borrowing): void {
                $lateDays = 0;

                if ($borrowing->return_date && $borrowing->due_date) {
                    $lateDays = max(0, (int) Carbon::parse($borrowing->due_date)
                        ->startOfDay()
                        ->diffInDays(Carbon::parse($borrowing->return_date)->startOfDay()));
                }

                DB::table('borrowings')
                    ->where('id', $borrowing->id)
                    ->update([
                        'status' => BorrowingStatus::Returned->value,
                        'late_days' => $lateDays,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropColumn('late_days');
        });
    }
};
