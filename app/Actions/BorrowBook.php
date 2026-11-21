<?php

namespace App\Actions;

use App\Enums\BorrowingStatus;
use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowBook
{
    /**
     * Proses peminjaman buku dalam satu transaksi database.
     *
     * Stok buku dikunci (lockForUpdate) agar dua petugas tidak bisa
     * meminjam buku yang sama pada waktu bersamaan.
     *
     * @param  array{user_id: int, book_id: int, duration_days: int}  $data
     */
    public function handle(array $data, ?int $adminId = null): Borrowing
    {
        return DB::transaction(function () use ($data, $adminId): Borrowing {
            $book = Book::query()
                ->whereKey($data['book_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($book->available_stock < 1) {
                throw ValidationException::withMessages([
                    'book_id' => 'Stok buku "'.$book->title.'" sedang kosong.',
                ]);
            }

            $durationDays = (int) $data['duration_days'];
            $borrowDate = now()->startOfDay();

            $borrowing = Borrowing::create([
                'user_id' => $data['user_id'],
                'admin_id' => $adminId,
                'book_id' => $book->id,
                'borrow_date' => $borrowDate,
                'due_date' => $borrowDate->copy()->addDays($durationDays),
                'duration_days' => $durationDays,
                'status' => BorrowingStatus::Borrowed,
            ]);

            $book->decrement('available_stock');

            return $borrowing;
        });
    }
}
