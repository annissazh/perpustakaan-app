<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->anggota(),
            'book_id' => Book::factory(),
            'admin_id' => null,
            'status' => ReservationStatus::Pending->value,
            'reserved_at' => now(),
            'fulfilled_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Reservasi yang sudah dikonversi menjadi peminjaman.
     */
    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Fulfilled->value,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Reservasi yang dibatalkan.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }
}
