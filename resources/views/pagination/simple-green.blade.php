{{-- Pagination sederhana memakai style toko sendiri (tanpa Tailwind/Bootstrap) --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="page-link disabled">&lsaquo; Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-link" rel="prev">&lsaquo; Sebelumnya</a>
        @endif

        <span class="page-info">
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
            &middot; {{ $paginator->total() }} data
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-link" rel="next">Berikutnya &rsaquo;</a>
        @else
            <span class="page-link disabled">Berikutnya &rsaquo;</span>
        @endif
    </nav>
@endif
