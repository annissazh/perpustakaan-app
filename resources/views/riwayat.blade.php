@extends('layouts.app')

@section('title', 'Riwayat Saya - PerpusApp')
@section('page-title', 'Riwayat Transaksi Peminjaman')

@section('content')
@php
    $sedangDipinjam = $borrowings->whereIn('status', [
        \App\Enums\BorrowingStatus::Borrowed,
        \App\Enums\BorrowingStatus::Overdue,
    ])->count();
    $sudahKembali = $borrowings->where('status', \App\Enums\BorrowingStatus::Returned)->count();
    $totalDendaUser = $borrowings->sum('fine_amount');
@endphp

<!-- Ringkasan Statistik Peminjaman Pengguna -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body px-4 py-3 d-flex align-items-center">
                <div class="stats-icon purple me-3 flex-shrink-0">
                    <i class="bi bi-book-half"></i>
                </div>
                <div>
                    <h6 class="text-muted font-semibold mb-1">Sedang Dipinjam</h6>
                    <h5 class="font-extrabold mb-0">{{ $sedangDipinjam }} Buku</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body px-4 py-3 d-flex align-items-center">
                <div class="stats-icon green me-3 flex-shrink-0">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted font-semibold mb-1">Selesai Dikembalikan</h6>
                    <h5 class="font-extrabold mb-0">{{ $sudahKembali }} Transaksi</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body px-4 py-3 d-flex align-items-center">
                <div class="stats-icon red me-3 flex-shrink-0">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div>
                    <h6 class="text-muted font-semibold mb-1">Riwayat Denda</h6>
                    <h5 class="font-extrabold mb-0 {{ $totalDendaUser > 0 ? 'text-danger' : '' }}">
                        Rp{{ number_format($totalDendaUser, 0, ',', '.') }}
                    </h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Riwayat Peminjaman -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 pb-2">
                <div>
                    <h4 class="card-title mb-1">Daftar Semua Transaksi Anda</h4>
                    <p class="text-muted small mb-0">Memuat seluruh riwayat buku yang sedang dan pernah Anda pinjam.</p>
                </div>
                <a href="{{ route('katalog.index') }}" class="btn btn-primary btn-sm align-self-start align-self-md-center">
                    <i class="bi bi-journal-bookmark-fill me-1"></i> Telusuri Katalog
                </a>
            </div>

            <div class="card-body">
                @if($borrowings->isEmpty())
                    <!-- Empty State Bersih (Tanpa Duplikasi Tabel Kosong DataTables) -->
                    <div class="text-center py-5">
                        <div class="bg-light-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <i class="bi bi-inbox fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Belum Ada Riwayat Peminjaman</h5>
                        <p class="text-muted mb-3">Anda belum pernah melakukan peminjaman buku di perpustakaan.</p>
                        <a href="{{ route('katalog.index') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i> Cari Buku Sekarang
                        </a>
                    </div>
                @else
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="tableSearch" class="form-control border-start-0"
                                       placeholder="Cari judul buku..." autocomplete="off">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-center justify-content-md-end">
                            <span class="perpus-table-counter" id="tableCounter"></span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="table1">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 55px;">No</th>
                                    <th>Judul Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Batas Kembali</th>
                                    <th>Tanggal Dikembalikan</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Denda</th>
                                    <th class="text-center" style="width: 130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($borrowings as $index => $item)
                                    @php
                                        $dueDate = $item->due_date?->copy()->startOfDay() ?? now()->startOfDay();
                                        $isLate = $item->isOverdue();
                                        $isDueToday = $item->status === \App\Enums\BorrowingStatus::Borrowed
                                            && $item->return_date === null
                                            && $dueDate->isToday();
                                    @endphp
                                    <tr>
                                        <td class="text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item->book->title ?? 'Buku Dihapus' }}</div>
                                            @if($item->book)
                                                <small class="text-muted">
                                                    <i class="bi bi-person me-1"></i>{{ $item->book->author }}
                                                    @if($item->duration_days)
                                                        &bull; Durasi {{ $item->duration_days }} hari
                                                    @endif
                                                </small>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($item->borrow_date)->format('d M Y') }}</td>
                                        <td>
                                            <span class="{{ $isLate ? 'text-danger fw-bold' : '' }}">
                                                {{ $dueDate->format('d M Y') }}
                                            </span>
                                            @if($isLate)
                                                <div class="text-danger small mt-1">
                                                    <i class="bi bi-exclamation-circle me-1"></i>Lewat tenggat
                                                </div>
                                            @elseif($isDueToday)
                                                <div class="text-warning small mt-1">
                                                    <i class="bi bi-clock me-1"></i>Jatuh tempo hari ini
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->return_date)
                                                <span>{{ \Carbon\Carbon::parse($item->return_date)->format('d M Y') }}</span>
                                            @else
                                                <span class="text-muted fst-italic">Belum kembali</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->status === \App\Enums\BorrowingStatus::Borrowed)
                                                <span class="badge bg-light-warning text-warning fw-semibold">
                                                    <i class="bi bi-hourglass-split me-1"></i>{{ $isLate ? 'Terlambat' : 'Sedang Dipinjam' }}
                                                </span>
                                            @elseif($item->status === \App\Enums\BorrowingStatus::Returned)
                                                <span class="badge bg-light-success text-success fw-semibold">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Dikembalikan
                                                </span>
                                            @elseif($item->status === \App\Enums\BorrowingStatus::Overdue)
                                                <span class="badge bg-light-danger text-danger fw-semibold">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Terlambat
                                                </span>
                                            @elseif($item->status === \App\Enums\BorrowingStatus::Lost)
                                                <span class="badge bg-light-dark text-dark fw-semibold">
                                                    <i class="bi bi-x-octagon-fill me-1"></i>Hilang
                                                </span>
                                            @else
                                                <span class="badge bg-light-secondary text-dark fw-semibold">
                                                    {{ $item->status->label() }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($item->fine_amount > 0)
                                                <span class="badge bg-light-danger text-danger fw-bold">
                                                    Rp{{ number_format($item->fine_amount, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($item->status->isActive())
                                                <form action="{{ route('riwayat.kembalikan', $item) }}" method="POST"
                                                      onsubmit="return confirm('Kembalikan buku {{ addslashes($item->book->title ?? 'ini') }}?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm fw-semibold">
                                                        <i class="bi bi-box-arrow-in-left me-1"></i>Kembalikan
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="perpus-table-empty" id="tableEmpty" hidden>
                            <i class="bi bi-search"></i>
                            <p class="mb-0 fw-semibold">Riwayat tidak ditemukan</p>
                            <small>Coba ubah kata kunci pencarian Anda.</small>
                        </div>
                    </div>

                    <div class="mt-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                        {{ $borrowings->links() }}
                        <span class="perpus-table-counter small">
                            Menampilkan {{ $borrowings->firstItem() ?? 0 }}-{{ $borrowings->lastItem() ?? 0 }} dari {{ $borrowings->total() }} riwayat
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@include('partials.table-search-script')