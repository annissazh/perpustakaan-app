<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
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

        $outstandingFine = (int) $user->borrowings()
            ->get(['fine_amount'])
            ->sum('fine_amount')
            - (int) $user->finePayments()->sum('amount');

        $pendingReservations = $user->reservations()
            ->with('book')
            ->pending()
            ->latest()
            ->get();

        return view('profile.show', [
            'user' => $user,
            'memberCode' => $memberCode,
            'qrData' => $qrData,
            'activeBorrowings' => $activeBorrowings,
            'totalBorrowings' => $totalBorrowings,
            'outstandingFine' => max(0, $outstandingFine),
            'pendingReservations' => $pendingReservations,
        ]);
    }

    /**
     * Memperbarui profil pengguna yang sedang login.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'name' => $request->string('name')->toString(),
            'phone' => $request->filled('phone') ? $request->string('phone')->toString() : null,
            'address' => $request->filled('address') ? $request->string('address')->toString() : null,
            'institution' => $request->filled('institution') ? $request->string('institution')->toString() : null,
            'birth_date' => $request->filled('birth_date') ? $request->date('birth_date') : null,
            'gender' => $request->filled('gender') ? $request->string('gender')->toString() : null,
        ]);

        return redirect()->route('profile.show')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
