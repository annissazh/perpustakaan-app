<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BorrowingDueReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly Borrowing $borrowing) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $borrowing = $this->borrowing;
        $daysLeft = $borrowing->due_date?->diffInDays(now()->startOfDay(), false) ?? 0;

        return (new MailMessage)
            ->subject('Pengingat: buku "'.$borrowing->book?->title.'" segera jatuh tempo')
            ->line('Halo '.$notifiable->name.',')
            ->line('Buku yang Anda pinjam akan segera melewati batas pengembalian.')
            ->line('Judul: '.$borrowing->book?->title)
            ->line('Tenggat: '.$borrowing->due_date?->format('d M Y'))
            ->line($daysLeft >= 0
                ? 'Sisa waktu '.$daysLeft.' hari.'
                : 'Buku ini sudah terlambat '.abs($daysLeft).' hari.')
            ->action('Lihat Riwayat Peminjaman', route('riwayat.index'))
            ->line('Terima kasih telah menggunakan layanan perpustakaan kami.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'borrowing_due',
            'borrowing_id' => $this->borrowing->id,
            'book_title' => $this->borrowing->book?->title ?? 'Buku',
            'due_date' => $this->borrowing->due_date?->format('d M Y'),
            'is_overdue' => $this->borrowing->isOverdue(),
            'url' => route('riwayat.index'),
        ];
    }
}
