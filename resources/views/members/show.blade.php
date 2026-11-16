@extends('layouts.app')

@section('title', 'Detail Anggota - PerpusApp')
@section('page-title', 'Detail Informasi Anggota')

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Profil Anggota</h4>
                <a href="/members" class="btn btn-sm btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Nama</th>
                        <td>{{ $member->name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $member->email }}</td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td><span class="badge bg-info">Anggota</span></td>
                    </tr>
                    <tr>
                        <th>Bergabung Sejak</th>
                        <td>{{ $member->created_at ? $member->created_at->format('d M Y') : '-' }}</td>
                    </tr>
                </table>
                <a href="/members/{{ $member->id }}/edit" class="btn btn-warning w-100 mt-2">
                    <i class="bi bi-pencil-square"></i> Edit Profil Anggota
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Riwayat Peminjaman Buku</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Judul Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Tenggat</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($borrowings as $item)
                                <tr>
                                    <td>{{ $item->book->title ?? 'Buku Dihapus' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->borrow_date)->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->due_date)->format('d M Y') }}</td>
                                    <td>
                                        @if($item->status == 'borrowed')
                                            <span class="badge bg-warning">Dipinjam</span>
                                        @elseif($item->status == 'returned')
                                            <span class="badge bg-success">Dikembalikan</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($item->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Anggota ini belum pernah meminjam buku.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection