<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;

it('menyimpan buku baru dengan stok tersedia sama dengan stok total', function () {
    $petugas = User::factory()->petugas()->create();
    $category = Category::factory()->create();

    $this->actingAs($petugas)
        ->post(route('books.store'), [
            'title' => 'Belajar Laravel 13',
            'category_id' => $category->id,
            'author' => 'Ahmad Fauzi',
            'publisher' => 'Penerbit Baca',
            'isbn' => '978-602-000-000-1',
            'published_year' => 2024,
            'total_stock' => 4,
        ])
        ->assertRedirect(route('books.index'));

    $book = Book::where('isbn', '978-602-000-000-1')->firstOrFail();

    expect($book->total_stock)->toBe(4)
        ->and($book->available_stock)->toBe(4);
});

it('membolehkan penerbit kosong', function () {
    $petugas = User::factory()->petugas()->create();
    $category = Category::factory()->create();

    $this->actingAs($petugas)
        ->post(route('books.store'), [
            'title' => 'Buku Tanpa Penerbit',
            'category_id' => $category->id,
            'author' => 'Anonim',
            'isbn' => '978-602-000-000-2',
            'published_year' => 2023,
            'total_stock' => 2,
        ])
        ->assertRedirect(route('books.index'));

    $this->assertDatabaseHas('books', ['isbn' => '978-602-000-000-2', 'publisher' => null]);
});

it('menolak isbn duplikat', function () {
    $petugas = User::factory()->petugas()->create();
    $category = Category::factory()->create();

    Book::factory()->create(['isbn' => '978-602-000-000-3']);

    $this->actingAs($petugas)
        ->post(route('books.store'), [
            'title' => 'Buku Duplikat',
            'category_id' => $category->id,
            'author' => 'Anonim',
            'isbn' => '978-602-000-000-3',
            'published_year' => 2023,
            'total_stock' => 2,
        ])
        ->assertSessionHasErrors('isbn');
});

it('menolak total stok lebih kecil dari jumlah buku yang dipinjam', function () {
    $petugas = User::factory()->petugas()->create();
    $category = Category::factory()->create();
    $book = Book::factory()->create([
        'category_id' => $category->id,
        'total_stock' => 3,
        'available_stock' => 3,
    ]);

    // Dua eksemplar sedang dipinjam.
    Borrowing::factory()->count(2)->create(['book_id' => $book->id]);

    $this->actingAs($petugas)
        ->put(route('books.update', $book), [
            'title' => $book->title,
            'category_id' => $category->id,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_year' => $book->published_year,
            'total_stock' => 1,
        ])
        ->assertSessionHasErrors('total_stock');
});

it('menolak penghapusan buku yang masih dipinjam', function () {
    $petugas = User::factory()->petugas()->create();
    $book = Book::factory()->create();
    Borrowing::factory()->create(['book_id' => $book->id]);

    $this->actingAs($petugas)
        ->delete(route('books.destroy', $book))
        ->assertRedirect(route('books.index'));

    $this->assertDatabaseHas('books', ['id' => $book->id]);
});

it('mencari katalog berdasarkan kata kunci', function () {
    $petugas = User::factory()->petugas()->create();
    Category::factory()->create(['name' => 'Pemrograman']);
    Category::factory()->create(['name' => 'Sastra']);

    Book::factory()->create(['title' => 'Belajar PHP Modern']);
    Book::factory()->create(['title' => 'Novel Sastra Indonesia']);

    $this->actingAs($petugas)
        ->get(route('katalog.index', ['search' => 'PHP']))
        ->assertOk()
        ->assertViewHas('books', fn ($books) => $books->total() === 1);
});

it('memerlukan minimal tiga huruf untuk live search', function () {
    $petugas = User::factory()->petugas()->create();
    Book::factory()->create(['title' => 'Belajar PHP Modern']);

    $this->actingAs($petugas)
        ->get(route('katalog.search', ['search' => 'PH']))
        ->assertOk()
        ->assertExactJson([]);

    $this->actingAs($petugas)
        ->get(route('katalog.search', ['search' => 'PHP']))
        ->assertOk()
        ->assertJsonCount(1);
});
