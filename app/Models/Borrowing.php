<?php

namespace App\Models;

use App\Enums\BorrowingStatus;
use Database\Factories\BorrowingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    /** @use HasFactory<BorrowingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'admin_id',
        'book_id',
        'borrow_date',
        'due_date',
        'duration_days',
        'return_date',
        'late_days',
        'status',
        'fine_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'duration_days' => 'integer',
            'late_days' => 'integer',
            'fine_amount' => 'integer',
            'status' => BorrowingStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Peminjaman yang masih aktif (buku belum dikembalikan).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [BorrowingStatus::Borrowed->value, BorrowingStatus::Overdue->value])
            ->whereNull('return_date');
    }

    /**
     * Peminjaman yang sudah lewat tenggat namun belum dikembalikan.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()
            ->whereDate('due_date', '<', now()->toDateString());
    }

    /**
     * True jika buku masih dipinjam dan sudah melewati tanggal tenggat.
     */
    public function isOverdue(): bool
    {
        if ($this->return_date !== null || $this->status !== BorrowingStatus::Borrowed) {
            return false;
        }

        return $this->due_date !== null
            && $this->due_date->copy()->endOfDay()->isPast();
    }

    /**
     * True jika transaksi masih berjalan (buku belum kembali).
     */
    public function isActive(): bool
    {
        return $this->return_date === null && $this->status->isActive();
    }
}
