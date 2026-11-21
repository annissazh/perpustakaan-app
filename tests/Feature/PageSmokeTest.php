<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceived;

/**
 * Smoke test: merender SETIAP halaman GET untuk SETIAP role.
 *
 * Tujuannya menangkap error runtime (undef variable, enum mismatch, null
 * property) yang tidak tertangkap oleh test fungsional biasa.
 */
function seedPerpusData(): array
{
    $category = Category::factory()->create();
    $book = Book::factory()->create(['category_id' => $category->id]);
    $bookLain = Book::factory()->create(['category_id' => $category->id]);
    $anggota = User::factory()->anggota()->create();

    Borrowing::factory()->create(['user_id' => $anggota->id, 'book_id' => $book->id]);
    Borrowing::factory()->overdue(5)->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
        'admin_id' => null,
    ]);
    Borrowing::factory()->returned()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
        'admin_id' => null,
    ]);

    // Ulasan dari anggota lain agar halaman ulasan tidak kosong.
    Review::factory()->create([
        'user_id' => $anggota->id,
        'book_id' => $book->id,
        'rating' => 4,
        'comment' => 'Buku yang sangat membantu.',
    ]);

    return [
        'category' => $category,
        'book' => $book,
        'bookLain' => $bookLain,
        'anggota' => $anggota,
    ];
}

/**
 * Daftar halaman GET: nama => [nama route, kunci parameter model].
 *
 * Kunci parameter diisi saat test berjalan (butuh koneksi database),
 * sehingga dataset tetap bisa didefinisikan secara statis.
 *
 * @return array<string, array{0: string, 1: array<string, string>}>
 */
function daftarHalamanGet(): array
{
    return [
        'dashboard' => ['dashboard', []],
        'katalog' => ['katalog.index', []],
        'riwayat' => ['riwayat.index', []],
        'profil' => ['profile.show', []],
        'notifikasi' => ['notifications.index', []],
        'buku show' => ['books.show', ['book']],
        'ulasan buku' => ['reviews.index', ['book']],
        'laporan' => ['analytics.index', []],
        'laporan 6 bulan' => ['analytics.index', []],
        'laporan 12 bulan' => ['analytics.index', []],
        'pengaturan' => ['settings.index', []],
        'anggota' => ['members.index', []],
        'anggota cari' => ['members.index', []],
        'anggota status' => ['members.index', []],
        'anggota detail' => ['members.show', ['id']],
        'anggota form' => ['members.create', []],
        'anggota edit' => ['members.edit', ['id']],
        'buku data' => ['books.index', []],
        'buku form' => ['books.create', []],
        'buku edit' => ['books.edit', ['book']],
        'kategori' => ['categories.index', []],
        'kategori edit' => ['categories.edit', ['category']],
        'sirkulasi' => ['borrowings.index', []],
        'sirkulasi form' => ['borrowings.create', []],
        'pengguna' => ['users.index', []],
        'pengguna form' => ['users.create', []],
        'pengguna edit' => ['users.edit', ['user']],
    ];
}

/**
 * Membangun parameter route dari kunci model.
 *
 * @param  array<string, string>  $kunci
 * @return array<string, mixed>
 */
function parameterRoute(array $kunci, array $data, array $query = []): array
{
    $parameter = [];

    foreach ($kunci as $key) {
        $parameter[$key] = match ($key) {
            'id' => $data['anggota']->id,
            'user' => $data['anggota'],
            default => $data[$key],
        };
    }

    return array_merge($parameter, $query);
}

function datasetHalaman(): array
{
    $dataset = [];

    foreach (daftarHalamanGet() as $nama => [$route, $kunci]) {
        $dataset[$nama] = [$route, $kunci];
    }

    return $dataset;
}

it('merender daftar anggota dengan filter terlambat tanpa error', function () {
    $petugas = User::factory()->petugas()->create();
    $terlambat = User::factory()->anggota()->create(['name' => 'Anggota Terlambat']);
    $normal = User::factory()->anggota()->create(['name' => 'Anggota Normal']);

    Borrowing::factory()->overdue(5)->create([
        'user_id' => $terlambat->id,
        'admin_id' => null,
    ]);
    Borrowing::factory()->create([
        'user_id' => $normal->id,
        'admin_id' => null,
        'due_date' => now()->startOfDay()->addDays(5),
    ]);

    $response = $this->actingAs($petugas)
        ->get(route('members.index', ['status' => 'terlambat']));

    $response->assertOk()
        ->assertSee('Anggota Terlambat')
        ->assertDontSee('Anggota Normal');
});

it('merender daftar anggota dengan filter tidak meminjam tanpa error', function () {
    $petugas = User::factory()->petugas()->create();
    $aktif = User::factory()->anggota()->create(['name' => 'Anggota Aktif']);
    $nonaktif = User::factory()->anggota()->create(['name' => 'Anggota Nonaktif']);

    Borrowing::factory()->create(['user_id' => $aktif->id, 'admin_id' => null]);

    $response = $this->actingAs($petugas)
        ->get(route('members.index', ['status' => 'nonaktif']));

    $response->assertOk()
        ->assertSee('Anggota Nonaktif')
        ->assertDontSee('Anggota Aktif');
});

it('merender daftar anggota dengan pencarian nama dan email', function () {
    $petugas = User::factory()->petugas()->create();
    User::factory()->anggota()->create([
        'name' => 'Cahyani Hamzah',
        'email' => 'cahyani@perpus.test',
    ]);
    User::factory()->anggota()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@perpus.test',
    ]);

    $this->actingAs($petugas)
        ->get(route('members.index', ['search' => 'cahyani']))
        ->assertOk()
        ->assertSee('Cahyani Hamzah')
        ->assertDontSee('Budi Santoso');
});

it('merender analitik tanpa data transaksi sama sekali', function () {
    $petugas = User::factory()->petugas()->create();

    $this->actingAs($petugas)
        ->get(route('analytics.index'))
        ->assertOk()
        ->assertSee('Belum ada data peminjaman.');
});

it('merender analitik dengan rentang bulan yang tidak valid tanpa error', function () {
    $petugas = User::factory()->petugas()->create();

    $this->actingAs($petugas)
        ->get(route('analytics.index', ['range' => 99]))
        ->assertOk();
});

it('merender katalog tanpa kategori yang cocok', function () {
    $anggota = User::factory()->anggota()->create();
    Book::factory()->create(['title' => 'Buku Tersembunyi']);

    $this->actingAs($anggota)
        ->get(route('katalog.index', ['search' => 'TidakAdaYangSama']))
        ->assertOk()
        ->assertDontSee('Buku Tersembunyi');
});

it('merender notifikasi kosong tanpa error', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Belum ada notifikasi untuk Anda.');
});

it('merender pengaturan dengan nilai default saat belum pernah disimpan', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('settings.index'))
        ->assertOk()
        ->assertSee('Maksimal Durasi Pinjam');
});

it('merender buku yang belum memiliki ulasan tanpa error', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();

    $this->actingAs($anggota)
        ->get(route('books.show', $book))
        ->assertOk()
        ->assertSee('Belum ada ulasan untuk buku ini.');
});

it('merender profil anggota tanpa error', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('profile.show'))
        ->assertOk();
});

it('menampilkan form ulasan dengan urutan radio rating dari lima ke satu', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();

    $response = $this->actingAs($anggota)->get(route('books.show', $book));

    $response->assertOk();

    $html = $response->getContent();

    // Radio harus urut 5 -> 1 agar general sibling selector CSS benar.
    $positions = [];
    foreach ([5, 4, 3, 2, 1] as $rating) {
        $positions[] = strpos($html, 'id="rating'.$rating.'"');
    }

    expect($positions)->not->toContain(false);

    $sorted = $positions;
    sort($sorted);

    expect($sorted)->toBe($positions, 'Radio rating harus dirender berurutan dari 5 ke 1.');
});

it('menyediakan kolom pencarian dan empty state pada halaman data', function (string $role, string $route) {
    $petugas = User::factory()->{$role}()->create();
    User::factory()->anggota()->create(['name' => 'Anggota searchable']);

    $response = $this->actingAs($petugas)->get(route($route));

    $response->assertOk()
        ->assertSee('id="tableSearch"', false)
        ->assertSee('id="tableEmpty"', false);
})->with([
    'anggota' => ['petugas', 'members.index'],
    'buku' => ['petugas', 'books.index'],
    'pengguna' => ['admin', 'users.index'],
    'kategori' => ['petugas', 'categories.index'],
    'sirkulasi' => ['petugas', 'borrowings.index'],
]);

it('menampilkan pratinjau rating dan penghitung karakter pada form ulasan', function () {
    $anggota = User::factory()->anggota()->create();
    $book = Book::factory()->create();

    $this->actingAs($anggota)
        ->get(route('books.show', $book))
        ->assertOk()
        ->assertSee('id="ratingPreview"', false)
        ->assertSee('id="commentCounter"', false);
});

it('menolak menandai notifikasi milik pengguna lain sebagai dibaca', function () {
    $pemilik = User::factory()->anggota()->create();
    $orangLain = User::factory()->anggota()->create();

    $pemilik->notifyNow(new ReviewReceived(
        Review::factory()->create([
            'user_id' => $pemilik->id,
            'book_id' => Book::factory()->create()->id,
        ])
    ));

    $notifikasi = $pemilik->notifications()->latest()->firstOrFail();

    $this->actingAs($orangLain)
        ->post(route('notifications.read', $notifikasi->id))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($notifikasi->fresh()->read_at)->toBeNull();
});

it('menandai notifikasi milik sendiri sebagai dibaca', function () {
    $anggota = User::factory()->anggota()->create();

    $anggota->notifyNow(new ReviewReceived(
        Review::factory()->create([
            'user_id' => $anggota->id,
            'book_id' => Book::factory()->create()->id,
        ])
    ));

    $notifikasi = $anggota->notifications()->latest()->firstOrFail();

    $this->actingAs($anggota)
        ->post(route('notifications.read', $notifikasi->id))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($notifikasi->fresh()->read_at)->not->toBeNull();
});

it('merender seluruh halaman untuk setiap role tanpa error', function (string $role, array $halaman) {
    $data = seedPerpusData();
    $pengguna = User::factory()->{$role}()->create();

    foreach ($halaman as $nama => [$route, $kunci, $query]) {
        $parameter = parameterRoute($kunci, $data, $query);

        $response = $this->actingAs($pengguna)->get(route($route, $parameter));

        $response->assertOk("Halaman [{$nama}] gagal dirender untuk role {$role}.");
    }
})->with([
    'anggota' => ['anggota', [
        'dashboard' => ['dashboard', [], []],
        'katalog' => ['katalog.index', [], []],
        'riwayat' => ['riwayat.index', [], []],
        'profil' => ['profile.show', [], []],
        'notifikasi' => ['notifications.index', [], []],
        'buku show' => ['books.show', ['book'], []],
        'ulasan buku' => ['reviews.index', ['book'], []],
    ]],
    'petugas' => ['petugas', [
        'dashboard' => ['dashboard', [], []],
        'katalog' => ['katalog.index', [], []],
        'riwayat' => ['riwayat.index', [], []],
        'profil' => ['profile.show', [], []],
        'notifikasi' => ['notifications.index', [], []],
        'buku show' => ['books.show', ['book'], []],
        'ulasan buku' => ['reviews.index', ['book'], []],
        'laporan' => ['analytics.index', [], []],
        'laporan 6 bulan' => ['analytics.index', [], ['range' => 6]],
        'laporan 12 bulan' => ['analytics.index', [], ['range' => 12]],
        'anggota' => ['members.index', [], []],
        'anggota cari' => ['members.index', [], ['search' => 'a']],
        'anggota status' => ['members.index', [], ['status' => 'terlambat']],
        'anggota detail' => ['members.show', ['id'], []],
        'anggota form' => ['members.create', [], []],
        'anggota edit' => ['members.edit', ['id'], []],
        'buku data' => ['books.index', [], []],
        'buku form' => ['books.create', [], []],
        'buku edit' => ['books.edit', ['book'], []],
        'kategori' => ['categories.index', [], []],
        'kategori edit' => ['categories.edit', ['category'], []],
        'sirkulasi' => ['borrowings.index', [], []],
        'sirkulasi form' => ['borrowings.create', [], []],
    ]],
    'admin' => ['admin', [
        'dashboard' => ['dashboard', [], []],
        'laporan' => ['analytics.index', [], []],
        'pengaturan' => ['settings.index', [], []],
        'anggota' => ['members.index', [], []],
        'pengguna' => ['users.index', [], []],
        'pengguna form' => ['users.create', [], []],
        'pengguna edit' => ['users.edit', ['user'], []],
        'sirkulasi' => ['borrowings.index', [], []],
        'buku data' => ['books.index', [], []],
    ]],
    'superadmin' => ['superadmin', [
        'dashboard' => ['dashboard', [], []],
        'laporan' => ['analytics.index', [], []],
        'pengaturan' => ['settings.index', [], []],
        'anggota' => ['members.index', [], []],
        'pengguna' => ['users.index', [], []],
        'pengguna form' => ['users.create', [], []],
        'pengguna edit' => ['users.edit', ['user'], []],
        'sirkulasi' => ['borrowings.index', [], []],
        'buku data' => ['books.index', [], []],
        'kategori' => ['categories.index', [], []],
    ]],
]);
