<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\Borrowing;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBuku = Book::count();
        $totalStokBuku = Book::sum('available_stock');
        $booksData = Book::orderBy('title', 'asc')->get();
        $bukuDipinjam = Borrowing::where('status', 'borrowed')->count();
        $totalAnggota = User::where('role', 'anggota')->count();

        $pinjamanSaya = 0;
        if (Auth::user()->role == 'anggota') {
            $pinjamanSaya = Borrowing::where('user_id', Auth::id())
                                     ->where('status', 'borrowed')
                                     ->count();
        }

        return view('dashboard', compact('totalBuku', 'totalStokBuku', 'booksData', 'bukuDipinjam', 'totalAnggota', 'pinjamanSaya'));
    }
}