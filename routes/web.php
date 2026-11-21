<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route untuk yang belum login (Guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Route untuk yang sudah login (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard, katalog, dan riwayat dapat diakses semua role.
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/katalog', [BookController::class, 'katalog'])->name('katalog.index');
    Route::get('/katalog/search-ajax', [BookController::class, 'searchAjax'])->name('katalog.search');
    Route::get('/riwayat', [BorrowingController::class, 'history'])->name('riwayat.index');

    // Manajemen pengguna: admin (superadmin mendapat akses penuh lewat middleware role).
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Operasional perpustakaan: admin & petugas.
    Route::middleware('role:admin,petugas')->group(function () {
        // Kategori buku.
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Buku.
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
        Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::get('/members/{id}', [MemberController::class, 'show'])->name('members.show');
        Route::get('/members/{id}/edit', [MemberController::class, 'edit'])->name('members.edit');
        Route::put('/members/{id}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{id}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Sirkulasi & laporan.
        Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
        Route::get('/borrowings/report-pdf', [BorrowingController::class, 'reportPdf'])->name('borrowings.report');
        Route::get('/borrowings/create', [BorrowingController::class, 'create'])->name('borrowings.create');
        Route::post('/borrowings', [BorrowingController::class, 'store'])->name('borrowings.store');
        Route::post('/borrowings/{borrowing}/return', [BorrowingController::class, 'returnBook'])->name('borrowings.return');
    });

    Route::middleware('role:anggota')->group(function () {
        Route::post('/katalog/pinjam/{book}', [BorrowingController::class, 'pinjamMandiri'])->name('katalog.pinjam');
        Route::post('/riwayat/kembalikan/{borrowing}', [BorrowingController::class, 'kembalikanMandiri'])->name('riwayat.kembalikan');
    });
});
