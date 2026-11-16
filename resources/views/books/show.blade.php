@extends('layouts.app')

@section('title', 'Detail Buku - PerpusApp')
@section('page-title', 'Detail Informasi Buku')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">{{ $book->title }}</h4>
                <a href="/books" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        @if($book->cover_image)
                            <img src="{{ asset('storage/' . $book->cover_image) }}" class="img-fluid rounded shadow-sm" alt="Sampul Buku" style="max-height: 350px; object-fit: cover;">
                        @else
                            <div class="bg-light rounded d-flex justify-content-center align-items-center" style="height: 350px;">
                                <i class="bi bi-book text-secondary" style="font-size: 5rem;"></i>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-8">
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Judul Buku</th>
                                <td>{{ $book->title }}</td>
                            </tr>
                            <tr>
                                <th>Kategori</th>
                                <td><span class="badge bg-primary">{{ $book->category->name ?? 'Tanpa Kategori' }}</span></td>
                            </tr>
                            <tr>
                                <th>Penulis</th>
                                <td>{{ $book->author }}</td>
                            </tr>
                            <tr>
                                <th>Penerbit</th>
                                <td>{{ $book->publisher ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>ISBN</th>
                                <td><code>{{ $book->isbn }}</code></td>
                            </tr>
                            <tr>
                                <th>Tahun Terbit</th>
                                <td>{{ $book->published_year }}</td>
                            </tr>
                            <tr>
                                <th>Total Stok</th>
                                <td>{{ $book->total_stock }} eksemplar</td>
                            </tr>
                            <tr>
                                <th>Stok Tersedia</th>
                                <td>
                                    <span class="badge {{ $book->available_stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $book->available_stock }} eksemplar
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <div class="mt-3">
                            <a href="/books/{{ $book->id }}/edit" class="btn btn-warning">
                                <i class="bi bi-pencil-square"></i> Edit Buku
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection