@extends('layouts.app')

@section('title', 'Manajemen Anggota - PerpusApp')
@section('page-title', 'Data Anggota Perpustakaan')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h4 class="card-title mb-1">Daftar Anggota</h4>
                    <p class="text-muted small mb-0">Kelola profil dan akun anggota perpustakaan.</p>
                </div>
                <a href="{{ route('members.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota
                </a>
            </div>
            <div class="card-body">
                @if($members->isEmpty())
                    <div class="text-center py-5">
                        <div class="bg-light-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <i class="bi bi-person-badge fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Belum Ada Anggota</h5>
                        <p class="text-muted mb-3">Tambahkan anggota pertama untuk mulai layanan peminjaman.</p>
                        <a href="{{ route('members.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-plus me-1"></i> Tambah Anggota
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="table1">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 55px;">No</th>
                                    <th>Nama Anggota</th>
                                    <th>Email</th>
                                    <th>Tgl Terdaftar</th>
                                    <th class="text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $index => $member)
                                    <tr>
                                        <td class="text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm flex-shrink-0">
                                                    <span class="avatar-content text-uppercase">{{ substr($member->name, 0, 1) }}</span>
                                                </div>
                                                <strong class="text-dark">{{ $member->name }}</strong>
                                            </div>
                                        </td>
                                        <td><span class="text-muted">{{ $member->email }}</span></td>
                                        <td>{{ $member->created_at?->format('d M Y') ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="table-action-buttons justify-content-center">
                                                <a href="{{ route('members.show', $member->id) }}" class="btn btn-sm btn-light-primary text-primary" data-bs-toggle="tooltip" title="Detail">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                <a href="{{ route('members.edit', $member->id) }}" class="btn btn-sm btn-light-warning text-warning" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <form action="{{ route('members.destroy', $member->id) }}" method="POST" class="form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger text-danger" data-bs-toggle="tooltip" title="Hapus">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection