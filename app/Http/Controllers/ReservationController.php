<?php

namespace App\Http\Controllers;

use App\Actions\FulfilReservation;
use App\Actions\ReserveBook;
use App\Enums\ReservationStatus;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /**
     * Daftar reservasi. Anggota hanya melihat reservasinya sendiri,
     * petugas melihat seluruh reservasi yang masuk.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isStaff = $user->role->isStaff();

        $reservations = Reservation::query()
            ->with(['user', 'book'])
            ->when(! $isStaff, fn ($query) => $query->where('user_id', $user->id))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => $reservations->total(),
            'pending' => Reservation::query()
                ->when(! $isStaff, fn ($query) => $query->where('user_id', $user->id))
                ->pending()
                ->count(),
            'fulfilled' => Reservation::query()
                ->when(! $isStaff, fn ($query) => $query->where('user_id', $user->id))
                ->where('status', ReservationStatus::Fulfilled->value)
                ->count(),
        ];

        return view('reservations.index', compact('reservations', 'stats'));
    }

    /**
     * Membuat reservasi baru untuk sebuah buku.
     */
    public function store(StoreReservationRequest $request, ReserveBook $reserveBook): RedirectResponse
    {
        try {
            $reserveBook->handle($request->user()->id, $request->book());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('reservations.index')
            ->with('success', 'Buku berhasil direservasi. Kami akan menginformasikan saat buku tersedia.');
    }

    /**
     * Membatalkan reservasi milik sendiri (anggota) atau oleh petugas.
     */
    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();

        if (! $user->role->isStaff() && (int) $reservation->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! $reservation->status->isActive()) {
            return back()->with('error', 'Reservasi ini sudah tidak berstatus menunggu.');
        }

        $reservation->update([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Reservasi berhasil dibatalkan.');
    }

    /**
     * Mengubah reservasi menjadi peminjaman nyata (hanya petugas).
     */
    public function fulfil(
        Request $request,
        Reservation $reservation,
        FulfilReservation $fulfilReservation
    ): RedirectResponse {
        try {
            $fulfilReservation->handle($reservation, $request->user()->id);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Reservasi berhasil dikonversi menjadi peminjaman.');
    }
}
