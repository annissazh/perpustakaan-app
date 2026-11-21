@extends('layouts.app')

@section('title', 'Kartu & Profil - PerpusApp')
@section('page-title', 'Kartu Anggota Digital')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card perpus-member-card mb-0" id="memberCardPrint">
            <div class="perpus-member-card__strip"></div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="perpus-member-card__brand">PerpusApp</span>
                        <p class="text-muted small mb-0">Kartu Anggota Digital</p>
                    </div>
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ $qrData }}"
                        alt="QR {{ $memberCode }}"
                        class="perpus-member-card__qr rounded"
                        width="100"
                        height="100"
                        loading="lazy"
                    >
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar avatar-xl">
                        <span class="avatar-content text-uppercase fs-4">{{ substr($user->name, 0, 1) }}</span>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                        <span class="badge {{ $user->role->badgeClass() }} fw-semibold mt-1">
                            {{ $user->role->label() }}
                        </span>
                    </div>
                </div>
                <ul class="perpus-info-list mb-0">
                    <li>
                        <span class="label">No. Anggota</span>
                        <span class="value font-monospace text-primary">{{ $memberCode }}</span>
                    </li>
                    <li>
                        <span class="label">Email</span>
                        <span class="value">{{ $user->email }}</span>
                    </li>
                    <li>
                        <span class="label">Bergabung</span>
                        <span class="value">{{ $user->created_at?->format('d M Y') ?? '-' }}</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn btn-primary flex-grow-1" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Kartu
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-light-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="card card-stat h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted small mb-1">Sedang Dipinjam</h6>
                        <h4 class="font-extrabold mb-0">{{ $activeBorrowings->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card card-stat h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted small mb-1">Total Transaksi</h6>
                        <h4 class="font-extrabold mb-0">{{ $totalBorrowings }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-0">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-journal-bookmark text-primary me-1"></i> Peminjaman Aktif</h5>
            </div>
            <div class="card-body">
                @forelse($activeBorrowings as $item)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom perpus-activity-row">
                        <div>
                            <div class="fw-semibold">{{ $item->book->title ?? 'Buku' }}</div>
                            <small class="text-muted">Jatuh tempo {{ $item->due_date?->format('d M Y') }}</small>
                        </div>
                        @if($item->isOverdue())
                            <span class="badge bg-light-danger text-danger">Terlambat</span>
                        @else
                            <span class="badge bg-light-warning text-warning">Aktif</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center py-3 mb-0">Tidak ada peminjaman aktif saat ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        #sidebar, .perpus-topbar, .perpus-footer, .btn, .sidebar-backdrop { display: none !important; }
        #main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .perpus-member-card { box-shadow: none; border: 2px solid #0d9488; }
    }
</style>
@endpush
