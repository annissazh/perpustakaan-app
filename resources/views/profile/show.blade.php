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
                    @if($user->phone)
                        <li>
                            <span class="label">Telepon</span>
                            <span class="value">{{ $user->phone }}</span>
                        </li>
                    @endif
                    @if($user->institution)
                        <li>
                            <span class="label">Instansi</span>
                            <span class="value">{{ $user->institution }}</span>
                        </li>
                    @endif
                    @if($user->birth_date)
                        <li>
                            <span class="label">Tanggal Lahir</span>
                            <span class="value">{{ $user->birth_date->format('d M Y') }}</span>
                        </li>
                    @endif
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
            <div class="col-6">
                <div class="card card-stat h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted small mb-1">Sisa Denda</h6>
                        <h4 class="font-extrabold mb-0 {{ $outstandingFine > 0 ? 'text-danger' : 'text-success' }}">
                            Rp{{ number_format($outstandingFine, 0, ',', '.') }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card card-stat h-100 mb-0">
                    <div class="card-body py-3">
                        <h6 class="text-muted small mb-1">Reservasi Menunggu</h6>
                        <h4 class="font-extrabold mb-0 text-warning">{{ $pendingReservations->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>

        @if($pendingReservations->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bookmark-heart text-warning me-1"></i> Reservasi Menunggu
                    </h5>
                    <a href="{{ route('reservations.index') }}" class="btn btn-light-secondary btn-sm">Kelola</a>
                </div>
                <div class="card-body py-2">
                    <ul class="list-unstyled mb-0">
                        @foreach($pendingReservations as $reservation)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <span class="fw-semibold text-dark">{{ $reservation->book->title ?? 'Buku Terhapus' }}</span>
                                    <small class="text-muted d-block">Reservasi {{ $reservation->reserved_at?->format('d M Y') }}</small>
                                </div>
                                <span class="badge bg-light-warning text-warning fw-semibold">Menunggu</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-person-gear text-primary me-1"></i> Edit Profil</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label fw-semibold">Nomor Telepon</label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="institution" class="form-label fw-semibold">Instansi / Asal</label>
                            <input type="text" id="institution" name="institution"
                                   class="form-control @error('institution') is-invalid @enderror"
                                   value="{{ old('institution', $user->institution) }}" placeholder="Nama kampus / kantor">
                            @error('institution')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="birth_date" class="form-label fw-semibold">Tanggal Lahir</label>
                            <input type="date" id="birth_date" name="birth_date"
                                   class="form-control @error('birth_date') is-invalid @enderror"
                                   value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Alamat</label>
                            <textarea id="address" name="address" rows="2" maxlength="255"
                                      class="form-control @error('address') is-invalid @enderror"
                                      placeholder="Alamat lengkap Anda">{{ old('address', $user->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Simpan Profil
                        </button>
                    </div>
                </form>
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
