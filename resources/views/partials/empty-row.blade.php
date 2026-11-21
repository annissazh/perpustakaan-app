{{--
    Partial: baris pesan "belum ada data" di dalam tabel.

    Parameter:
    - $colspan : jumlah kolom tabel
    - $icon    : ikon Bootstrap Icons
    - $title   : judul pesan
    - $text    : penjelasan
    - $actionUrl    : (opsional) URL tombol aksi
    - $actionLabel  : (opsional) label tombol aksi
    - $actionIcon   : (opsional) ikon tombol aksi
--}}
<tr data-searchable="false">
    <td colspan="{{ $colspan ?? 1 }}">
        <div class="text-center py-5">
            <div class="bg-light-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                 style="width: 72px; height: 72px;">
                <i class="bi {{ $icon ?? 'bi-inbox' }} fs-1"></i>
            </div>
            <h5 class="fw-bold mb-1">{{ $title ?? 'Belum ada data' }}</h5>
            <p class="text-muted mb-3">{{ $text ?? '' }}</p>
            @if(! empty($actionUrl))
                <a href="{{ $actionUrl }}" class="btn btn-primary btn-sm">
                    <i class="bi {{ $actionIcon ?? 'bi-plus-circle' }} me-1"></i> {{ $actionLabel ?? 'Tambah' }}
                </a>
            @endif
        </div>
    </td>
</tr>
