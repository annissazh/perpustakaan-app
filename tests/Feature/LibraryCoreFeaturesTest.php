<?php

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\FinePayment;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\BookAvailable;

it('anggota dapat membuat reservasi untuk buku yang stoknya habis', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 0]);

    $this->actingAs($anggota)
        ->post(route('reservations.store'), ['book_id' => $book->id])
        ->assertRedirect(route('reservations.index'));

    expect(Reservation::query()->count())->toBe(1)
        ->and(Reservation::query()->first()->status)->toBe(ReservationStatus::Pending);
});

it('menolak reservasi untuk buku yang masih tersedia', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 3, 'available_stock' => 3]);

    $this->actingAs($anggota)
        ->post(route('reservations.store'), ['book_id' => $book->id])
        ->assertSessionHasErrors('book_id');

    expect(Reservation::query()->count())->toBe(0);
});

it('menolak reservasi ganda untuk buku yang sama', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 0]);

    Reservation::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
    ]);

    $this->actingAs($anggota)
        ->post(route('reservations.store'), ['book_id' => $book->id])
        ->assertSessionHasErrors('book_id');
});

it('anggota dapat reservasi ulang buku yang pernah direservasi sebelumnya', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 0]);

    // Reservasi lama sudah dibatalkan.
    Reservation::factory()->cancelled()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
    ]);

    $this->actingAs($anggota)
        ->post(route('reservations.store'), ['book_id' => $book->id])
        ->assertRedirect(route('reservations.index'));

    expect(Reservation::query()->count())->toBe(1)
        ->and(Reservation::query()->first()->status)->toBe(ReservationStatus::Pending);
});

it('anggota hanya melihat reservasinya sendiri', function () {
    $anggota = User::factory()->anggota()->create();
    $anggotaLain = User::factory()->anggota()->create();

    Reservation::factory()->create(['user_id' => $anggota->id]);
    Reservation::factory()->create(['user_id' => $anggotaLain->id]);

    $this->actingAs($anggota)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertViewHas('reservations', fn ($items) => $items->count() === 1);
});

it('anggota tidak dapat membatalkan reservasi milik orang lain', function () {
    $anggota = User::factory()->anggota()->create();
    $reservation = Reservation::factory()->create([
        'user_id' => User::factory()->anggota()->create()->id,
    ]);

    $this->actingAs($anggota)
        ->delete(route('reservations.destroy', $reservation))
        ->assertForbidden();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Pending);
});

it('petugas dapat menerbitkan reservasi menjadi peminjaman', function () {
    $petugas = User::factory()->petugas()->create();
    $anggota = User::factory()->anggota()->create();

    // Buku sudah kembali ke rak sehingga stok tersedia saat reservasi diterbitkan.
    $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 1]);

    $reservation = Reservation::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
    ]);

    $this->actingAs($petugas)
        ->post(route('reservations.fulfil', $reservation))
        ->assertRedirect();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Fulfilled)
        ->and(Borrowing::query()->where('user_id', $anggota->id)->count())->toBe(1)
        ->and($book->refresh()->available_stock)->toBe(0);
});

it('petugas dapat memperpanjang masa peminjaman', function () {
    $petugas = User::factory()->petugas()->create();
    $borrowing = Borrowing::factory()->create([
        'due_date' => now()->startOfDay()->addDays(3),
        'extension_count' => 0,
    ]);

    $this->actingAs($petugas)
        ->post(route('borrowings.extend', $borrowing))
        ->assertRedirect(route('borrowings.index'));

    $borrowing->refresh();

    expect($borrowing->extension_count)->toBe(1)
        ->and($borrowing->due_date->greaterThan(now()->startOfDay()->addDays(3)))->toBeTrue();
});

it('menolak perpanjangan untuk peminjaman yang sudah terlambat', function () {
    $petugas = User::factory()->petugas()->create();
    $borrowing = Borrowing::factory()->overdue(3)->create(['extension_count' => 0]);

    $this->actingAs($petugas)
        ->post(route('borrowings.extend', $borrowing))
        ->assertSessionHasErrors('status');

    expect($borrowing->refresh()->extension_count)->toBe(0);
});

it('menolak perpanjangan melebihi batas maksimum', function () {
    SystemSetting::set('maksimal_perpanjangan', 1);

    $petugas = User::factory()->petugas()->create();
    $borrowing = Borrowing::factory()->create([
        'due_date' => now()->startOfDay()->addDays(3),
        'extension_count' => 1,
    ]);

    $this->actingAs($petugas)
        ->post(route('borrowings.extend', $borrowing))
        ->assertSessionHasErrors('status');

    SystemSetting::flush();
});

it('anggota tidak dapat memperpanjang peminjaman sendiri', function () {
    $anggota = User::factory()->anggota()->create();
    $borrowing = Borrowing::factory()->create(['user_id' => $anggota->id]);

    // Middleware role mengarahkan anggota kembali ke dashboard.
    $this->actingAs($anggota)
        ->post(route('borrowings.extend', $borrowing))
        ->assertRedirect(route('dashboard'));

    expect($borrowing->refresh()->extension_count)->toBe(0);
});

it('petugas dapat mencatat pembayaran denda', function () {
    $petugas = User::factory()->petugas()->create();
    $borrowing = Borrowing::factory()->create(['fine_amount' => 50000]);

    $this->actingAs($petugas)
        ->post(route('fines.store', $borrowing), [
            'amount' => 20000,
            'method' => PaymentMethod::Cash->value,
            'reference' => 'TRX-001',
        ])
        ->assertRedirect();

    expect(FinePayment::query()->count())->toBe(1)
        ->and($borrowing->refresh()->outstandingFine())->toBe(30000);
});

it('menolak pembayaran yang melebihi sisa denda', function () {
    $petugas = User::factory()->petugas()->create();
    $borrowing = Borrowing::factory()->create(['fine_amount' => 10000]);

    $this->actingAs($petugas)
        ->post(route('fines.store', $borrowing), [
            'amount' => 50000,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('amount');

    expect(FinePayment::query()->count())->toBe(0);
});

it('menolak pembayaran pada transaksi tanpa denda', function () {
    $petugas = User::factory()->petugas()->create();
    $borrowing = Borrowing::factory()->create(['fine_amount' => 0]);

    $this->actingAs($petugas)
        ->post(route('fines.store', $borrowing), [
            'amount' => 10000,
            'method' => PaymentMethod::Cash->value,
        ])
        ->assertSessionHasErrors('amount');
});

it('anggota tidak dapat mengakses halaman pembayaran denda', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('fines.index'))
        ->assertRedirect(route('dashboard'));
});

it('pengguna dapat memperbarui profilnya sendiri', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->put(route('profile.update'), [
            'name' => 'Nama Baru Anggota',
            'phone' => '08123456789',
            'institution' => 'Universitas Nusantara',
            'birth_date' => '2000-05-12',
            'address' => 'Jl. Merdeka No. 10',
        ])
        ->assertRedirect(route('profile.show'));

    $anggota->refresh();

    expect($anggota->name)->toBe('Nama Baru Anggota')
        ->and($anggota->phone)->toBe('08123456789')
        ->and($anggota->birth_date->format('Y-m-d'))->toBe('2000-05-12');
});

it('menolak tanggal lahir di masa depan', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->put(route('profile.update'), [
            'name' => $anggota->name,
            'birth_date' => now()->addYear()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('birth_date');
});

it('halaman profil menampilkan form edit profil', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Edit Profil')
        ->assertSee('name="phone"', false);
});

it('mengirim notifikasi buku tersedia ketika buku dikembalikan', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 0]);

    $borrowing = Borrowing::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
    ]);

    Reservation::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
    ]);

    $this->actingAs(User::factory()->petugas()->create())
        ->post(route('borrowings.return', $borrowing), ['status' => 'returned'])
        ->assertRedirect(route('borrowings.index'));

    expect($anggota->notifications()->count())->toBe(1)
        ->and($anggota->notifications()->first()->type)->toBe(BookAvailable::class);
});

it('command reservasi memberi tahu anggota ketika buku tersedia', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 1]);

    Reservation::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
    ]);

    $this->artisan('app:process-reservations')->assertSuccessful();

    expect($anggota->notifications()->count())->toBe(1);
});

it('command reservasi menandai reservasi lama sebagai kedaluwarsa', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create(['total_stock' => 1, 'available_stock' => 0]);

    $reservation = Reservation::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
        'reserved_at' => now()->subDays(5),
    ]);

    $this->artisan('app:process-reservations')->assertSuccessful();

    expect($reservation->refresh()->status)->toBe(ReservationStatus::Expired);
});
