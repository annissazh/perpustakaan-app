<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\FinePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinePayment extends Model
{
    /** @use HasFactory<FinePaymentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_id',
        'user_id',
        'admin_id',
        'amount',
        'method',
        'reference',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
