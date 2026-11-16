<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class BorrowingController extends Controller
{
    // Menampilkan riwayat transaksi DENGAN FILTER (Untuk Admin/Petugas)
    public function index(Request $request)
    {
        $query = Borrowing::with(['user', 'admin', 'book'])->latest();

        // 1. Filter Berdasarkan Pilihan Cepat (Harian/Bulanan/Tahunan)
        if ($request->has('filter') && $request->filter != '') {
            $now = Carbon::now();
            if ($request->filter == 'hari_ini') {
                $query->whereDate('borrow_date', $now->toDateString());
            } elseif ($request->filter == 'bulan_ini') {
                $query->whereMonth('borrow_date', $now->month)
                      ->whereYear('borrow_date', $now->year);
            } elseif ($request->filter == 'tahun_ini') {
                $query->whereYear('borrow_date', $now->year);
            }
        }

        // 2. Filter Berdasarkan Rentang Tanggal Kustom (Start Date - End Date)
        if ($request->has('start_date') && $request->start_date != '' && $request->has('end_date') && $request->end_date != '') {
            $query->whereBetween('borrow_date', [$request->start_date, $request->end_date]);
        }

        $borrowings = $query->get();
        return view('borrowings.index', compact('borrowings'));
    }

    public function create()
    {
        $members = User::where('role', 'anggota')->get();
        $books = Book::where('available_stock', '>', 0)->get();
        return view('borrowings.create', compact('members', 'books'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'duration_days' => 'required|integer|min:1|max:30',
        ]);

        $book = Book::findOrFail($request->book_id);

        if ($book->available_stock < 1) {
            return back()->withErrors(['book_id' => 'Stok buku ini sedang kosong.']);
        }

        $borrowDate = Carbon::now();
        $dueDate = Carbon::now()->addDays($request->duration_days);

        Borrowing::create([
            'user_id' => $request->user_id,
            'admin_id' => Auth::id(),
            'book_id' => $request->book_id,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'duration_days' => $request->duration_days,
            'status' => 'borrowed',
        ]);

        $book->decrement('available_stock');
        return redirect('/borrowings')->with('success', 'Buku berhasil dipinjamkan!');
    }

    // Memproses Pengembalian atau Status Buku Hilang (Oleh Admin/Petugas)
    public function returnBook(Request $request, $id)
    {
        $borrowing = Borrowing::with('book')->findOrFail($id);

        if ($borrowing->status != 'borrowed') {
            return back()->withErrors(['error' => 'Buku ini sudah dikembalikan atau statusnya tidak valid.']);
        }

        $now = Carbon::now();
        $dueDate = Carbon::parse($borrowing->due_date);
        
        $status = $request->input('status', 'returned');
        $fineAmount = 0;

        if ($status == 'returned') {
            if ($now->greaterThan($dueDate)) {
                $daysLate = $dueDate->diffInDays($now);
                $finePerDay = $borrowing->book->fine_per_day ?? 1000;
                $fineAmount = $daysLate * $finePerDay;
                $status = 'overdue'; 
            }

            $borrowing->book->increment('available_stock');
        } else {
            $fineAmount = $request->input('lost_fine_amount', 50000);
            $status = 'lost';
        }

        $borrowing->update([
            'return_date' => $now,
            'status' => $status,
            'fine_amount' => $fineAmount,
        ]);

        $message = $fineAmount > 0 
            ? "Status transaksi diperbarui. Terdapat tagihan/denda sebesar Rp" . number_format($fineAmount, 0, ',', '.') 
            : "Buku berhasil dikembalikan tepat waktu.";

        return redirect('/borrowings')->with('success', $message);
    }

    // Menghasilkan PDF yang MENGIKUTI FILTER yang sedang aktif
    public function reportPdf(Request $request)
    {
        $query = Borrowing::with(['user', 'admin', 'book'])->orderBy('borrow_date', 'desc');

        if ($request->has('filter') && $request->filter != '') {
            $now = Carbon::now();
            if ($request->filter == 'hari_ini') {
                $query->whereDate('borrow_date', $now->toDateString());
            } elseif ($request->filter == 'bulan_ini') {
                $query->whereMonth('borrow_date', $now->month)
                      ->whereYear('borrow_date', $now->year);
            } elseif ($request->filter == 'tahun_ini') {
                $query->whereYear('borrow_date', $now->year);
            }
        }

        if ($request->has('start_date') && $request->start_date != '' && $request->has('end_date') && $request->end_date != '') {
            $query->whereBetween('borrow_date', [$request->start_date, $request->end_date]);
        }

        $borrowings = $query->get();

        $pdf = Pdf::loadView('borrowings.report', compact('borrowings'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->stream('laporan-sirkulasi-perpusapp.pdf');
    }

    // Method khusus untuk Halaman Riwayat Saya
    public function history()
    {
        $borrowings = Borrowing::where('user_id', Auth::id())
                        ->with('book')
                        ->orderBy('created_at', 'desc')
                        ->get();
                        
        return view('riwayat', compact('borrowings')); 
    }

    // Method Peminjaman Mandiri untuk Role Anggota
    public function pinjamMandiri(Request $request, $book_id)
    {
        $book = Book::findOrFail($book_id);

        if ($book->available_stock < 1) {
            return back()->with('error', 'Maaf, stok buku ini sedang kosong.');
        }

        $durationDays = $request->input('duration_days', 7);
        $borrowDate = Carbon::now();
        $dueDate = Carbon::now()->addDays($durationDays);

        Borrowing::create([
            'user_id'       => Auth::id(),
            'admin_id'      => null,
            'book_id'       => $book->id,
            'borrow_date'   => $borrowDate,
            'due_date'      => $dueDate,
            'duration_days' => $durationDays,
            'status'        => 'borrowed',
        ]);

        $book->decrement('available_stock');

        return redirect('/riwayat')->with('success', 'Buku berhasil dipinjam! Silakan cek di riwayat peminjaman.');
    }

    // 👇 TAMBAHAN BARU: Method Pengembalian Mandiri untuk Role Anggota
    public function kembalikanMandiri($id)
    {
        $borrowing = Borrowing::with('book')
                        ->where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        if ($borrowing->status != 'borrowed') {
            return back()->with('error', 'Buku ini sudah dikembalikan sebelumnya.');
        }

        $now = Carbon::now();
        $dueDate = Carbon::parse($borrowing->due_date);
        $status = 'returned';
        $fineAmount = 0;

        // Hitung denda keterlambatan jika melewati tenggat waktu
        if ($now->greaterThan($dueDate)) {
            $daysLate = $dueDate->diffInDays($now);
            $finePerDay = $borrowing->book->fine_per_day ?? 1000;
            $fineAmount = $daysLate * $finePerDay;
            $status = 'overdue';
        }

        // Kembalikan stok buku
        $borrowing->book->increment('available_stock');

        // Update status peminjaman
        $borrowing->update([
            'return_date' => $now,
            'status'      => $status,
            'fine_amount' => $fineAmount,
        ]);

        $message = $fineAmount > 0
            ? "Buku berhasil dikembalikan dengan keterlambatan. Denda: Rp" . number_format($fineAmount, 0, ',', '.')
            : "Buku berhasil dikembalikan tepat waktu!";

        return redirect('/riwayat')->with('success', $message);
    }
}