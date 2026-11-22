<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'admin_id',
        'status',
        'reserved_at',
        'fulfilled_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'reserved_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Reservasi yang masih menunggu buku tersedia.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::Pending->value);
    }

    /**
     * Reservasi yang masih berjalan (pending).
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
