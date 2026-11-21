@extends('layouts.app')

@section('title', 'Data Buku - PerpusApp')
@section('page-title', 'Manajemen Data Buku')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h4 class="card-title mb-1">Daftar Koleksi Buku</h4>
                    <p class="text-muted small mb-0">Kelola stok, sampul, dan informasi bibliografi koleksi perpustakaan.</p>
                </div>
                <a href="{{ route('books.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Buku
                </a>
            </div>

            <div class="card-body">
                @if($books->isEmpty())
                    <div class="text-center py-5">
                        <div class="bg-light-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 72px; height: 72px;">
                            <i class="bi bi-book fs-1"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Belum Ada Koleksi Buku</h5>
                        <p class="text-muted mb-3">Mulai tambahkan data buku baru ke dalam sistem perpustakaan.</p>
                        <a href="{{ route('books.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Buku Pertama
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="table1">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 65px;">Sampul</th>
                                    <th>Judul & Penulis</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Tahun</th>
                                    <th class="text-center">Stok Total</th>
                                    <th class="text-center">Stok Tersedia</th>
                                    <th class="text-center" style="width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($books as $book)
                                    <tr>
                                        <td>
                                            @if($book->cover_image)
                                                <img src="{{ asset('storage/' . $book->cover_image) }}"
                                                     alt="Sampul {{ $book->title }}"
                                                     class="rounded shadow-sm"
                                                     width="44"
                                                     height="62"
                                                     style="object-fit: cover;">
                                            @else
                                                <div class="bg-light border rounded d-flex justify-content-center align-items-center text-secondary" style="width: 44px; height: 62px;">
                                                    <i class="bi bi-journal-richtext fs-5 opacity-50"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $book->title }}</div>
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i>{{ $book->author }}
                                                @if($book->isbn)
                                                    &bull; ISBN: {{ $book->isbn }}
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary fw-semibold">
                                                {{ $book->category->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $book->published_year }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-light-secondary text-dark">{{ $book->total_stock }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($book->available_stock > 0)
                                                <span class="badge bg-light-success text-success fw-semibold">
                                                    {{ $book->available_stock }} Tersedia
                                                </span>
                                            @else
                                                <span class="badge bg-light-danger text-danger fw-semibold">Habis</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="table-action-buttons justify-content-center">
                                                <a href="{{ route('books.edit', $book) }}"
                                                   class="btn btn-sm btn-light-warning text-warning"
                                                   data-bs-toggle="tooltip"
                                                   title="Edit Buku">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <form action="{{ route('books.destroy', $book) }}" method="POST" class="form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-light-danger text-danger"
                                                            data-bs-toggle="tooltip"
                                                            title="Hapus Buku">
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