@props(['rows'])

@if ($rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $rows->total() > 0)
    @php
        $perPage = (int) $rows->perPage();
        $pageName = $rows->getPageName();
        $last = max(1, $rows->lastPage());
        $current = $rows->currentPage();
        $start = max(1, $current - 2);
        $end = min($last, $start + 4);
        $start = max(1, $end - 4);
    @endphp
    <nav class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Paginasi">
        <label class="flex items-center gap-2 text-wd-muted">
            <span>Show on page by</span>
            <select
                class="field-select w-20"
                wire:model.live="perPage"
                onchange="if (!this.closest('[wire\\:id]')) { const next = new URL(window.location.href); next.searchParams.set('per_page', this.value); ['page','recent','devices','logs','activity','stock','readings','days','products','shelves','posPage','matchedPage','reviewPage','unmatchedPage','pendingPage'].forEach((key) => next.searchParams.delete(key)); window.location = next; }"
            >
                @foreach ([10, 25, 50] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </label>
        <div class="flex items-center gap-1">
            @if ($rows->onFirstPage())
                <span class="page-link opacity-50" aria-hidden="true">&lt;</span>
            @else
                <a class="page-link" href="{{ $rows->previousPageUrl() }}" wire:click.prevent="previousPage('{{ $pageName }}')" aria-label="Halaman sebelumnya">&lt;</a>
            @endif
            @for ($page = $start; $page <= $end; $page++)
                @if ($page === $current)
                    <span class="page-link page-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="page-link" href="{{ $rows->url($page) }}" wire:click.prevent="gotoPage({{ $page }}, '{{ $pageName }}')" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                @endif
            @endfor
            @if ($rows->hasMorePages())
                <a class="page-link" href="{{ $rows->nextPageUrl() }}" wire:click.prevent="nextPage('{{ $pageName }}')" aria-label="Halaman berikutnya">&gt;</a>
            @else
                <span class="page-link opacity-50" aria-hidden="true">&gt;</span>
            @endif
        </div>
    </nav>
@endif
