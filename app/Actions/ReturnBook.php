<?php

namespace App\Actions;

use App\Enums\BorrowingStatus;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Notifications\BookAvailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnBook
{
    /**
     * Proses pengembalian (atau pencatatan buku hilang) dalam satu transaksi.
     *
     * Keterlambatan dihitung dari selisih tanggal (bukan jam) sehingga buku
     * yang dikembalikan tepat pada tanggal tenggat tidak incurs denda.
     */
    public function handle(Borrowing $borrowing, BorrowingStatus $status, ?int $lostFineAmount = null): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $status, $lostFineAmount): Borrowing {
            /** @var Borrowing $borrowing */
            $borrowing = Borrowing::query()
                ->whereKey($borrowing->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Book|null $book */
            $book = Book::query()
                ->whereKey($borrowing->book_id)
                ->lockForUpdate()
                ->first();

            if ($borrowing->return_date !== null || ! $borrowing->status->isActive()) {
                throw ValidationException::withMessages([
                    'status' => 'Buku ini sudah dikembalikan atau statusnya tidak valid.',
                ]);
            }

            $returnDate = now()->startOfDay();
            $dueDate = $borrowing->due_date?->copy()->startOfDay() ?? $returnDate->copy();
            $lateDays = max(0, (int) $dueDate->diffInDays($returnDate));
            $fineAmount = 0;

            if ($status === BorrowingStatus::Lost) {
                $fineAmount = $lostFineAmount ?? SystemSetting::getInt('denda_buku_hilang', 50000);
            } else {
                $finePerDay = $book?->fine_per_day ?? SystemSetting::getInt('denda_per_hari', 1000);
                $fineAmount = $lateDays * (int) $finePerDay;

                // Buku fisik kembali ke rak, sehingga stok tersedia bertambah satu.
                $book?->increment('available_stock');
            }

            $borrowing->update([
                'return_date' => $returnDate,
                'status' => $status,
                'late_days' => $lateDays,
                'fine_amount' => $fineAmount,
            ]);

            // Anggota yang menunggu buku ini diberi tahu bahwa buku tersedia.
            if ($book !== null && $status === BorrowingStatus::Returned) {
                $this->notifyWaitingMembers($book);
            }

            return $borrowing->refresh();
        });
    }

    /**
     * Memberi tahu anggota yang memiliki reservasi aktif atas buku tersebut.
     */
    private function notifyWaitingMembers(Book $book): void
    {
        Reservation::query()
            ->with('user')
            ->pending()
            ->where('book_id', $book->id)
            ->get()
            ->each(function (Reservation $reservation) use ($book): void {
                $reservation->user?->notify(new BookAvailable($book));
            });
    }
}
