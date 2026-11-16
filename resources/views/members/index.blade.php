@extends('layouts.app')

@section('title', 'Manajemen Anggota - PerpusApp')
@section('page-title', 'Data Anggota Perpustakaan')

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible show fade">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Anggota</h4>
                <a href="/members/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Anggota
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table1">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anggota</th>
                                <th>Email</th>
                                <th>Tanggal Terdaftar</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $index => $member)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $member->name }}</strong></td>
                                    <td>{{ $member->email }}</td>
                                    <td>{{ $member->created_at ? $member->created_at->format('d M Y') : '-' }}</td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="/members/{{ $member->id }}" class="btn btn-sm btn-info text-white">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <a href="/members/{{ $member->id }}/edit" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                            <form action="/members/{{ $member->id }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus anggota ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash-fill"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection