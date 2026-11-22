<?php

namespace App\Actions;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mengubah reservasi menjadi peminjaman nyata.
 *
 * Dipakai petugas ketika anggota mengambil buku yang sudah direservasi.
 */
class FulfilReservation
{
    /**
     * @throws ValidationException
     */
    public function handle(Reservation $reservation, ?int $adminId = null, ?int $durationDays = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $adminId, $durationDays): Reservation {
            /** @var Reservation $reservation */
            $reservation = Reservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $reservation->status->isActive()) {
                throw ValidationException::withMessages([
                    'status' => 'Reservasi ini sudah tidak berstatus menunggu.',
                ]);
            }

            $days = $durationDays ?? SystemSetting::getInt('maksimal_hari_pinjam', 7);

            app(BorrowBook::class)->handle([
                'user_id' => $reservation->user_id,
                'book_id' => $reservation->book_id,
                'duration_days' => $days,
            ], $adminId);

            $reservation->update([
                'status' => ReservationStatus::Fulfilled,
                'fulfilled_at' => now(),
                'admin_id' => $adminId,
            ]);

            return $reservation->refresh();
        });
    }
}
