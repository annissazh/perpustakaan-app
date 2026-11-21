<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\BorrowingDueReminder;

it('menampilkan halaman laporan analitik untuk petugas', function () {
    $petugas = User::factory()->petugas()->create();

    $this->actingAs($petugas)
        ->get(route('analytics.index'))
        ->assertOk()
        ->assertSee('Tren Peminjaman Bulanan');
});

it('menolak akses laporan untuk anggota', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('analytics.index'))
        ->assertRedirect(route('dashboard'));
});

it('menyimpan pengaturan sistem', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('settings.update'), [
            'denda_per_hari' => 2500,
            'maksimal_hari_pinjam' => 14,
            'denda_buku_hilang' => 75000,
            'nama_perpustakaan' => 'Perpus Kota',
            'alamat_perpustakaan' => 'Jl. Merdeka 1',
            'email_perpustakaan' => 'info@perpus.test',
            'telepon_perpustakaan' => '021-123456',
            'maksimal_buku_anggota' => 5,
            'hari_pengingat' => 2,
        ])
        ->assertRedirect(route('settings.index'));

    expect(SystemSetting::getInt('denda_per_hari', 0))->toBe(2500)
        ->and(SystemSetting::get('nama_perpustakaan'))->toBe('Perpus Kota');
});

it('menolak anggota menyimpan pengaturan sistem', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->put(route('settings.update'), [])
        ->assertRedirect(route('dashboard'));
});

it('anggota dapat memberikan ulasan buku', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();

    $this->actingAs($anggota)
        ->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'Buku yang sangat membantu.',
        ])
        ->assertRedirect(route('books.show', $book));

    expect(Review::query()->where('book_id', $book->id)->count())->toBe(1);
});

it('menolak ulasan ganda dari anggota yang sama', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();
    Review::factory()->create(['user_id' => $anggota->id, 'book_id' => $book->id]);

    $this->actingAs($anggota)
        ->post(route('reviews.store', $book), ['rating' => 3])
        ->assertRedirect();

    expect(Review::query()->where('book_id', $book->id)->count())->toBe(1);
});

it('petugas tidak dapat memberikan ulasan', function () {
    $petugas = User::factory()->petugas()->create();
    $book = Book::factory()->create();

    $this->actingAs($petugas)
        ->post(route('reviews.store', $book), ['rating' => 4])
        ->assertRedirect(route('dashboard'));

    expect(Review::query()->where('book_id', $book->id)->count())->toBe(0);
});

it('menampilkan daftar ulasan buku', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();
    Review::factory()->create(['user_id' => $anggota->id, 'book_id' => $book->id, 'rating' => 4]);

    $this->actingAs($anggota)
        ->get(route('reviews.index', $book))
        ->assertOk()
        ->assertSee('Semua Ulasan');
});

it('membatasi jumlah buku aktif per anggota', function () {
    SystemSetting::set('maksimal_buku_anggota', 1);

    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();
    Borrowing::factory()->create(['user_id' => $anggota->id, 'admin_id' => null]);

    $this->actingAs($anggota)
        ->post(route('katalog.pinjam', $book))
        ->assertSessionHasErrors('book_id');
});

it('mengirim pengingat jatuh tempo melalui command', function () {
    $anggota = User::factory()->anggota()->create();
    $borrowing = Borrowing::factory()->create([
        'user_id' => $anggota->id,
        'admin_id' => null,
        'due_date' => now()->startOfDay()->addDays(2),
    ]);

    $this->artisan('app:send-due-reminders')->assertSuccessful();

    expect($anggota->notifications()->count())->toBe(1)
        ->and($borrowing->fresh()->reminder_sent_at)->not->toBeNull();
});

it('menampilkan daftar notifikasi pengguna', function () {
    $anggota = User::factory()->anggota()->create();
    $borrowing = Borrowing::factory()->create(['user_id' => $anggota->id, 'admin_id' => null]);
    $anggota->notify(new BorrowingDueReminder($borrowing));

    $this->actingAs($anggota)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Kotak Masuk');
});

it('menandai notifikasi sebagai sudah dibaca', function () {
    $anggota = User::factory()->anggota()->create();
    $borrowing = Borrowing::factory()->create(['user_id' => $anggota->id, 'admin_id' => null]);
    $anggota->notify(new BorrowingDueReminder($borrowing));

    $notificationId = $anggota->notifications()->first()->id;

    $this->actingAs($anggota)
        ->post(route('notifications.read', $notificationId))
        ->assertRedirect();

    expect($anggota->fresh()->unreadNotifications->count())->toBe(0);
});

it('anggota dapat membuka detail buku untuk memberi ulasan', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();

    $this->actingAs($anggota)
        ->get(route('books.show', $book))
        ->assertOk()
        ->assertSee('Ulasan Pembaca')
        ->assertSee('Berikan Rating');
});

it('anggota tidak dapat membuka daftar data buku', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('books.index'))
        ->assertRedirect(route('dashboard'));
});

it('anggota tidak dapat membuka form tambah buku', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('books.create'))
        ->assertRedirect(route('dashboard'));
});
