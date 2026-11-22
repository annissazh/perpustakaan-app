@extends('layouts.app')

@section('title', 'Reservasi Buku - PerpusApp')
@section('page-title', 'Reservasi Buku')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body py-3">
                <h6 class="text-muted small mb-1">Total Reservasi</h6>
                <h4 class="font-extrabold mb-0">{{ $stats['total'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body py-3">
                <h6 class="text-muted small mb-1">Menunggu</h6>
                <h4 class="font-extrabold mb-0 text-warning">{{ $stats['pending'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body py-3">
                <h6 class="text-muted small mb-1">Selesai Dipinjam</h6>
                <h4 class="font-extrabold mb-0 text-success">{{ $stats['fulfilled'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-stat h-100 mb-0">
            <div class="card-body py-3 d-flex flex-column justify-content-center">
                <a href="{{ route('katalog.index') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Cari Buku
                </a>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h5 class="card-title mb-1">Daftar Reservasi</h5>
            <p class="text-muted small mb-0">
                @if(auth()->user()->role->isStaff())
                    Semua reservasi anggota yang masuk ke perpustakaan.
                @else
                    Reservasi buku yang sedang Anda tunggu.
                @endif
            </p>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                <option value="fulfilled" @selected(request('status') === 'fulfilled')>Selesai</option>
                <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                <option value="expired" @selected(request('status') === 'expired')>Kedaluwarsa</option>
            </select>
        </form>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="table1">
                <thead class="table-light">
                    <tr>
                        <th style="width: 55px;">No</th>
                        <th>Judul Buku</th>
                        @if(auth()->user()->role->isStaff())
                            <th>Anggota</th>
                        @endif
                        <th>Tanggal Reservasi</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 190px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservations as $index => $reservation)
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $reservation->book->title ?? 'Buku Terhapus' }}</div>
                                <small class="text-muted">{{ $reservation->book->author ?? '-' }}</small>
                            </td>
                            @if(auth()->user()->role->isStaff())
                                <td>
                                    <div class="fw-semibold text-dark">{{ $reservation->user->name ?? 'Anggota Terhapus' }}</div>
                                    <small class="text-muted">{{ $reservation->user->email ?? '-' }}</small>
                                </td>
                            @endif
                            <td>{{ $reservation->reserved_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge {{ $reservation->status->badgeClass() }} fw-semibold">
                                    {{ $reservation->status->label() }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($reservation->status->isActive())
                                    @if(auth()->user()->role->isStaff())
                                        <form action="{{ route('reservations.fulfil', $reservation) }}" method="POST" class="no-spinner d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                                                <i class="bi bi-journal-check me-1"></i> Terbitkan
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('reservations.destroy', $reservation) }}" method="POST" class="no-spinner d-inline form-delete">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger fw-semibold">
                                            <i class="bi bi-x-circle me-1"></i> Batalkan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if($reservations->isEmpty())
                        @include('partials.empty-row', [
                            'colspan' => auth()->user()->role->isStaff() ? 6 : 5,
                            'icon' => 'bi-bookmark-heart',
                            'title' => 'Belum Ada Reservasi',
                            'text' => 'Buku yang sedang dipinjam bisa direservasi langsung dari halaman katalog.',
                            'actionUrl' => route('katalog.index'),
                            'actionLabel' => 'Buka Katalog',
                            'actionIcon' => 'bi-search',
                        ])
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            {{ $reservations->links() }}
            <span class="perpus-table-counter small">
                Menampilkan {{ $reservations->firstItem() ?? 0 }}-{{ $reservations->lastItem() ?? 0 }} dari {{ $reservations->total() }} reservasi
            </span>
        </div>
    </div>
</div>
@endsection

