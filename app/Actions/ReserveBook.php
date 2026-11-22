<?php

namespace App\Actions;

use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Membuat reservasi buku untuk anggota.
 *
 * Reservasi hanya dibuat bila buku sedang tidak tersedia (semua
 * eksemplar dipinjam) sehingga anggota dapat menunggu buku yang
 * sedang dipinjam orang lain.
 */
class ReserveBook
{
    /**
     * @throws ValidationException
     */
    public function handle(int $userId, Book $book): Reservation
    {
        return DB::transaction(function () use ($userId, $book): Reservation {
            /** @var Book $book */
            $book = Book::query()
                ->whereKey($book->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = Reservation::query()
                ->where('user_id', $userId)
                ->where('book_id', $book->id)
                ->first();

            if ($existing !== null && $existing->status->isActive()) {
                throw ValidationException::withMessages([
                    'book_id' => 'Anda sudah memiliki reservasi aktif untuk buku ini.',
                ]);
            }

            if ($book->available_stock > 0) {
                throw ValidationException::withMessages([
                    'book_id' => 'Buku ini masih tersedia, Anda dapat langsung meminjamnya.',
                ]);
            }

            if ($existing !== null) {
                $existing->update([
                    'status' => ReservationStatus::Pending,
                    'reserved_at' => now(),
                    'fulfilled_at' => null,
                    'cancelled_at' => null,
                ]);

                return $existing->refresh();
            }

            return Reservation::create([
                'user_id' => $userId,
                'book_id' => $book->id,
                'status' => ReservationStatus::Pending,
                'reserved_at' => now(),
            ]);
        });
    }
}
