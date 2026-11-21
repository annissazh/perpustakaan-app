<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Kartu digital & ringkasan akun pengguna yang sedang login.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        $activeBorrowings = $user->activeBorrowings()->with('book')->orderBy('due_date')->get();
        $totalBorrowings = $user->borrowings()->count();
        $memberCode = $user->memberCode();
        $qrData = urlencode('PERPUSAPP|'.$memberCode.'|'.$user->email);

        return view('profile.show', [
            'user' => $user,
            'memberCode' => $memberCode,
            'qrData' => $qrData,
            'activeBorrowings' => $activeBorrowings,
            'totalBorrowings' => $totalBorrowings,
        ]);
    }
}
