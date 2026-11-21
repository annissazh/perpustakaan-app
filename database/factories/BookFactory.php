<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalStock = fake()->numberBetween(1, 10);

        return [
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'isbn' => sprintf(
                '978-%s-%s-%s',
                fake()->numerify('###-###'),
                fake()->numerify('##'),
                fake()->numerify('###')
            ),
            'published_year' => fake()->numberBetween(2000, (int) now()->year),
            'total_stock' => $totalStock,
            'available_stock' => $totalStock,
            'fine_per_day' => fake()->numberBetween(500, 5000),
        ];
    }

    /**
     * Buku dengan stok tersedia habis.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_stock' => 0,
        ]);
    }
}
