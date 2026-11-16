<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberController;

// Route untuk yang belum login (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    
    // Route untuk Katalog (Bisa diakses tanpa login jika diinginkan)
    Route::get('/katalog', [BookController::class, 'katalog']);
    Route::get('/katalog/search-ajax', [BookController::class, 'searchAjax']);
});

// Route untuk yang sudah login (Auth)
Route::middleware('auth')->group(function () {
    
    // Bisa diakses SEMUA ROLE (Admin, Petugas, Anggota)
    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/katalog', [BookController::class, 'katalog']);
    Route::get('/riwayat', [BorrowingController::class, 'history']);

    // HANYA bisa diakses oleh ADMIN
    Route::middleware('role:admin')->group(function () {
        // Route Manajemen User
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/create', [UserController::class, 'create']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}/edit', [UserController::class, 'edit']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Route Kelola Kategori (Hanya Admin; Petugas hanya bisa melihat)
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}/edit', [CategoryController::class, 'edit']); 
        Route::put('/categories/{id}', [CategoryController::class, 'update']);    
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    });

    // Bisa diakses oleh ADMIN & PETUGAS (Operasional Perpustakaan)
    Route::middleware('role:admin,petugas')->group(function () {
        // Petugas hanya bisa melihat daftar Kategori
        Route::get('/categories', [CategoryController::class, 'index']);

        // Route Kelola Buku (Admin & Petugas mengelola buku + Detail Buku)
        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/create', [BookController::class, 'create']);
        Route::post('/books', [BookController::class, 'store']);
        Route::get('/books/{id}', [BookController::class, 'show']); // <-- Ditambahkan untuk Detail Buku
        Route::get('/books/{id}/edit', [BookController::class, 'edit']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);

        Route::get('/members', [MemberController::class, 'index']);
        Route::get('/members/create', [MemberController::class, 'create']);
        Route::post('/members', [MemberController::class, 'store']);
        Route::get('/members/{id}', [MemberController::class, 'show']);
        Route::get('/members/{id}/edit', [MemberController::class, 'edit']);
        Route::put('/members/{id}', [MemberController::class, 'update']);
        Route::delete('/members/{id}', [MemberController::class, 'destroy']);

        // Route Sirkulasi & Laporan
        Route::get('/borrowings', [BorrowingController::class, 'index']);
        Route::get('/borrowings/report-pdf', [BorrowingController::class, 'reportPdf']);
        Route::get('/borrowings/create', [BorrowingController::class, 'create']);
        Route::post('/borrowings', [BorrowingController::class, 'store']);
        Route::post('/borrowings/{id}/return', [BorrowingController::class, 'returnBook']);
    });

    // role anggota
    Route::middleware('role:anggota')->group(function () {
        Route::post('/katalog/pinjam/{book_id}', [BorrowingController::class, 'pinjamMandiri'])->name('katalog.pinjam');
        Route::post('/riwayat/kembalikan/{id}', [BorrowingController::class, 'kembalikanMandiri'])->name('riwayat.kembalikan');
    });
});