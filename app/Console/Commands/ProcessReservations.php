<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Notifications\BookAvailable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:process-reservations')]
#[Description('Mengelola reservasi buku: pemberitahuan buku tersedia dan kedaluwarsa reservasi')]
class ProcessReservations extends Command
{
    /**
     * Berapa hari buku yang tersedia harus menunggu sebelum reservasi
     * dianggap kedaluwarsa.
     */
    private const RESERVATION_HOLD_DAYS = 3;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $notified = $this->notifyAvailableBooks();
        $expired = $this->expireStaleReservations();

        $this->info("Buku tersedia diberitahukan: {$notified}, reservasi kedaluwarsa: {$expired}.");

        return self::SUCCESS;
    }

    /**
     * Memberi tahu anggota yang reservasinya bisa dipenuhi karena stok tersedia.
     */
    private function notifyAvailableBooks(): int
    {
        $reservations = Reservation::query()
            ->with(['user', 'book'])
            ->pending()
            ->whereHas('book', fn ($query) => $query->where('available_stock', '>', 0))
            ->get();

        $notified = 0;

        foreach ($reservations as $reservation) {
            if ($reservation->user === null || $reservation->book === null) {
                continue;
            }

            // Hindari notifikasi berulang untuk reservasi yang sama.
            $alreadyNotified = $reservation->user->notifications()
                ->where('type', BookAvailable::class)
                ->where('data->book_id', $reservation->book_id)
                ->where('created_at', '>=', now()->subDay())
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $reservation->user->notify(new BookAvailable($reservation->book));
            $notified++;
        }

        return $notified;
    }

    /**
     * Menandai reservasi yang sudah lewat masa tunggu sebagai kedaluwarsa.
     */
    private function expireStaleReservations(): int
    {
        $cutoff = now()->subDays(self::RESERVATION_HOLD_DAYS);

        return Reservation::query()
            ->pending()
            ->where('reserved_at', '<=', $cutoff)
            ->update([
                'status' => ReservationStatus::Expired->value,
                'cancelled_at' => now(),
            ]);
    }
}
