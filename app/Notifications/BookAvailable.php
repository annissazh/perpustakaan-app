<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookAvailable extends Notification
{
    use Queueable;

    public function __construct(public readonly Book $book) {}

    /**
     * Get the notification's delivery channels.
     *
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
        return (new MailMessage)
            ->subject('Buku "'.$this->book->title.'" kini tersedia')
            ->line('Halo '.$notifiable->name.',')
            ->line('Buku yang Anda reservasi sekarang sudah tersedia di perpustakaan.')
            ->line('Judul: '.$this->book->title)
            ->action('Lihat Detail Buku', route('books.show', $this->book->id))
            ->line('Silakan segera ambil buku tersebut sebelum habis dipinjam.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'book_available',
            'book_id' => $this->book->id,
            'book_title' => $this->book->title,
            'url' => route('books.show', $this->book->id),
        ];
    }
}
