@extends('layouts.app')

@section('title', 'Notifikasi - PerpusApp')
@section('page-title', 'Notifikasi Saya')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="card mb-0">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h5 class="card-title mb-1">Kotak Masuk</h5>
                    <p class="text-muted small mb-0">
                        {{ auth()->user()->unreadNotifications->count() }} notifikasi belum dibaca.
                    </p>
                </div>
                @if(auth()->user()->unreadNotifications->isNotEmpty())
                    <form action="{{ route('notifications.read-all') }}" method="POST" class="no-spinner">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light-primary align-self-start align-self-md-center">
                            <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($notifications as $notification)
                        @php
                            $data = $notification->data;
                            $isUnread = $notification->read_at === null;
                        @endphp
                        <li class="list-group-item px-4 py-3 {{ $isUnread ? 'perpus-notification-unread' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="d-flex gap-3">
                                    <div class="perpus-notification-icon {{ ($data['type'] ?? '') === 'review_received' ? 'bg-light-primary text-primary' : 'bg-light-warning text-warning' }}">
                                        <i class="bi {{ ($data['type'] ?? '') === 'review_received' ? 'bi-star-fill' : 'bi-alarm-fill' }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            @if(($data['type'] ?? '') === 'review_received')
                                                Ulasan baru untuk "{{ $data['book_title'] ?? 'Buku' }}"
                                            @else
                                                Pengingat jatuh tempo: "{{ $data['book_title'] ?? 'Buku' }}"
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            @if(($data['type'] ?? '') === 'review_received')
                                                Rating {{ $data['rating'] ?? 0 }} dari 5 bintang.
                                            @else
                                                Tenggat {{ $data['due_date'] ?? '-' }}
                                                @if($data['is_overdue'] ?? false)
                                                    &bull; <span class="text-danger fw-semibold">sudah terlambat</span>
                                                @endif
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if(! empty($data['url']))
                                        <a href="{{ $data['url'] }}" class="btn btn-sm btn-light-secondary">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    @endif
                                    @if($isUnread)
                                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="no-spinner">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Tandai Dibaca</button>
                                        </form>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Dibaca</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-5">
                            <i class="bi bi-bell-slash d-block fs-2 mb-2 opacity-50"></i>
                            Belum ada notifikasi untuk Anda.
                        </li>
                    @endforelse
                </ul>
            </div>
            @if($notifications->hasPages())
                <div class="card-footer bg-transparent border-0">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection