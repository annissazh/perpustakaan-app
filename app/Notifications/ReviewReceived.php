<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReceived extends Notification
{
    use Queueable;

    public function __construct(public readonly Review $review) {}

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
        return (new MailMessage)
            ->subject('Ulasan baru untuk "'.$this->review->book?->title.'"')
            ->line('Buku "'.$this->review->book?->title.'" menerima ulasan baru.')
            ->line('Rating: '.$this->review->rating.' dari 5 bintang.')
            ->action('Lihat Detail Buku', route('books.show', $this->review->book_id))
            ->line('Ulasan ini membantu anggota lain menemukan buku yang tepat.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_received',
            'review_id' => $this->review->id,
            'book_title' => $this->review->book?->title ?? 'Buku',
            'rating' => $this->review->rating,
            'url' => route('books.show', $this->review->book_id),
        ];
    }
}
