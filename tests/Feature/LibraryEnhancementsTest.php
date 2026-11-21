<?php

use App\Models\Borrowing;
use App\Models\User;

it('merender halaman kartu profil untuk anggota', function () {
    $anggota = User::factory()->anggota()->create();

    $this->actingAs($anggota)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee($anggota->memberCode());
});

it('mengekspor csv laporan sirkulasi', function () {
    $petugas = User::factory()->petugas()->create();
    Borrowing::factory()->create(['admin_id' => $petugas->id]);

    $response = $this->actingAs($petugas)
        ->get(route('borrowings.report-csv'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('laporan-sirkulasi');
});

it('menampilkan member code konsisten berdasarkan id', function () {
    $user = User::factory()->anggota()->create();

    expect($user->memberCode())->toBe(sprintf('PA-%06d', $user->id));
});

it('dashboard memuat aktivitas terbaru', function () {
    $anggota = User::factory()->anggota()->create();
    Borrowing::factory()->create(['user_id' => $anggota->id, 'admin_id' => null]);

    $this->actingAs($anggota)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('recentActivities', fn ($items) => $items->count() === 1);
});
