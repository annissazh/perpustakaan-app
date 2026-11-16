@extends('layouts.app')

@section('title', 'Kategori Buku - PerpusApp')
@section('page-title', 'Manajemen Kategori')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Menampilkan Pesan Sukses -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible show fade">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Daftar Kategori</h4>
                
                <!-- 1. Tombol Tambah hanya untuk Admin -->
                @if(auth()->user()->role === 'admin')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i> Tambah Kategori
                    </button>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table1">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <!-- 2. Kolom Header Aksi hanya untuk Admin -->
                                @if(auth()->user()->role === 'admin')
                                    <th class="text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $index => $category)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $category->name }}</strong></td>
                                    
                                    <!-- 3. Tombol Edit & Hapus hanya untuk Admin -->
                                    @if(auth()->user()->role === 'admin')
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <!-- Tombol Edit (Memicu Modal Edit) -->
                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>

                                                <!-- Form Hapus Kategori -->
                                                <form action="/categories/{{ $category->id }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash-fill"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>

                                <!-- 4. Modal Edit Kategori hanya untuk Admin -->
                                @if(auth()->user()->role === 'admin')
                                    <div class="modal fade text-left" id="editModal{{ $category->id }}" tabindex="-1" role="dialog" aria-labelledby="editModalLabel{{ $category->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-warning">
                                                    <h4 class="modal-title text-white" id="editModalLabel{{ $category->id }}">Edit Kategori</h4>
                                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </div>
                                                <form action="/categories/{{ $category->id }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <label>Nama Kategori: </label>
                                                        <div class="form-group mt-2">
                                                            <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">
                                                            <span class="d-sm-block">Batal</span>
                                                        </button>
                                                        <button type="submit" class="btn btn-warning ml-1">
                                                            <span class="d-sm-block">Simpan Perubahan</span>
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->role === 'admin' ? 3 : 2 }}" class="text-center">Belum ada data kategori.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. Modal Tambah Kategori hanya untuk Admin -->
@if(auth()->user()->role === 'admin')
    <div class="modal fade text-left" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel33" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel33">Tambah Kategori Baru</h4>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <form action="/categories" method="POST">
                    @csrf
                    <div class="modal-body">
                        <label>Nama Kategori: </label>
                        <div class="form-group mt-2">
                            <input type="text" name="name" placeholder="Contoh: Pemrograman Web" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">
                            <span class="d-sm-block">Tutup</span>
                        </button>
                        <button type="submit" class="btn btn-primary ml-1">
                            <span class="d-sm-block">Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection