<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Halaman dashboard. Data yang ditampilkan dibatasi sesuai role pengguna
     * agar anggota tidak memuat seluruh data anggota, peminjaman, dan denda.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isStaff = $user->role->isStaff();
        $canViewMemberStats = $user->role->canManageUsers();

        // Menghitung jumlah JUDUL BUKU (bukan total stok fisik).
        $countBuku = Book::count();
        $countAnggota = $canViewMemberStats
            ? User::query()->where('role', Role::Anggota->value)->count()
            : 0;

        // Rincian koleksi (maksimal 200 judul terbaru).
        $booksList = Book::query()
            ->orderBy('title')
            ->take(200)
            ->get();

        if ($isStaff) {
            $dipinjamList = Borrowing::with(['user', 'book'])
                ->active()
                ->latest()
                ->take(200)
                ->get();

            $dendaList = Borrowing::with(['user', 'book'])
                ->where('fine_amount', '>', 0)
                ->latest()
                ->take(200)
                ->get();

            $anggotaList = User::query()
                ->where('role', Role::Anggota->value)
                ->latest()
                ->take(200)
                ->get();
        } else {
            // Anggota hanya melihat transaksi miliknya sendiri.
            $dipinjamList = Borrowing::with(['user', 'book'])
                ->where('user_id', $user->id)
                ->active()
                ->latest()
                ->get();

            $dendaList = Borrowing::with(['user', 'book'])
                ->where('user_id', $user->id)
                ->where('fine_amount', '>', 0)
                ->latest()
                ->get();

            // Anggota tidak boleh melihat daftar anggota lain.
            $anggotaList = collect();
        }

        return view('dashboard', [
            'isStaff' => $isStaff,
            'canViewMemberStats' => $canViewMemberStats,
            'countBuku' => $countBuku,
            'booksList' => $booksList,
            'countAnggota' => $countAnggota,
            'anggotaList' => $anggotaList,
            'countDipinjam' => $dipinjamList->count(),
            'dipinjamList' => $dipinjamList,
            'sumDenda' => $dendaList->sum('fine_amount'),
            'dendaList' => $dendaList,
            'pinjamanAktifSaya' => $dipinjamList->count(),
        ]);
    }
}
