@extends('layouts.app')

@section('title', 'Kategori Buku - PerpusApp')
@section('page-title', 'Manajemen Kategori')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h4 class="card-title mb-1">Daftar Kategori Buku</h4>
                    <p class="text-muted small mb-0">Kelola pengelompokan koleksi buku perpustakaan.</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                </button>
            </div>

            <div class="card-body">
                @if($categories->isEmpty())
                    <div class="text-center py-5">
                        <div class="bg-light-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <i class="bi bi-tags fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Belum Ada Kategori</h5>
                        <p class="text-muted mb-3">Tambahkan kategori pertama untuk mulai mengelompokkan koleksi buku.</p>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                        </button>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="table1">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 65px;">No</th>
                                    <th>Nama Kategori</th>
                                    <th class="text-center" style="width: 180px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $index => $category)
                                    <tr>
                                        <td class="text-muted">{{ $index + 1 }}</td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary me-2">
                                                <i class="bi bi-tag-fill"></i>
                                            </span>
                                            <strong class="text-dark">{{ $category->name }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <div class="table-action-buttons justify-content-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-light-warning text-warning fw-semibold"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editModal{{ $category->id }}"
                                                        title="Edit Kategori">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                                </button>

                                                <form action="{{ route('categories.update', $category) }}" method="POST" class="form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger text-danger fw-semibold" title="Hapus Kategori">
                                                        <i class="bi bi-trash-fill me-1"></i>Hapus
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

@push('modals')
<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header perpus-modal-header">
                <h5 class="modal-title text-white" id="addCategoryModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Kategori Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" placeholder="Contoh: Pemrograman Web" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori (Ditempatkan di luar tabel agar valid & bebas bug backdrop) -->
@foreach ($categories as $category)
<div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1" aria-labelledby="editModalLabel{{ $category->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header perpus-modal-header-warning">
                <h5 class="modal-title fw-bold" id="editModalLabel{{ $category->id }}">
                    <i class="bi bi-pencil-square me-2"></i>Edit Kategori
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endpush