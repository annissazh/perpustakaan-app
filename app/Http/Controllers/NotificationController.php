<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Menampilkan daftar notifikasi pengguna yang sedang login.
     */
    public function index(): View
    {
        $user = auth()->user();

        $notifications = $user->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Menandai satu notifikasi sebagai sudah dibaca.
     *
     * Notifikasi yang bukan milik pengguna yang sedang login diabaikan agar
     * ID milik orang lain tidak dapat dimanipulasi melalui URL.
     */
    public function read(string $notification): RedirectResponse
    {
        $user = auth()->user();

        $target = $user->notifications()->whereKey($notification)->first();

        if ($target === null) {
            return back()->with('error', 'Notifikasi tidak ditemukan.');
        }

        $target->markAsRead();

        return back()->with('success', 'Notifikasi ditandai telah dibaca.');
    }

    /**
     * Menandai seluruh notifikasi sebagai sudah dibaca.
     */
    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }
}
