{{--
    Partial: inisialisasi live search tabel.

    Dipakai oleh halaman yang menampilkan tabel data dengan
    `id="table1"` dan kolom pencarian `id="tableSearch"`.

    Variabel opsional (di-set sebelum @include):
    - $searchColumns : array<int, int> indeks kolom yang dicari.
--}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Perpus.tableSearch({
            input: '#tableSearch',
            table: '#table1',
            empty: '#tableEmpty',
            counter: '#tableCounter',
            columns: @json($searchColumns ?? null),
        });
    });
</script>
@endpush
