@php
    /** @var list<array{group: string, items: list<array<string, mixed>>}> $navGroups */
    $itemCount = collect($navGroups)->sum(fn ($group) => count($group['items']));
@endphp

@if ($itemCount <= 1)
    <div class="rounded-lg border border-wd-border bg-wd-sidebar px-3 py-3 text-sm text-wd-muted">
        Tidak ada modul rak yang diizinkan untuk akun ini. Hubungi admin retail.
    </div>
@endif

@if ($navGroups !== [])
    <nav class="space-y-4">
        @foreach ($navGroups as $group)
            <div>
                <div class="px-3 pb-1 text-[11px] font-medium uppercase tracking-wide text-wd-muted">{{ $group['group'] }}</div>
                <div class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @if ($item['url'])
                            <a
                                href="{{ $item['url'] }}"
                                @click="typeof sidebarOpen !== 'undefined' && (sidebarOpen = false)"
                                class="flex items-center rounded-lg px-3 py-2 text-sm transition
                                    {{ $item['active']
                                        ? 'bg-wd-bg font-medium text-wd-accent'
                                        : 'text-wd-ink hover:bg-wd-bg hover:text-wd-accent' }}"
                            >{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>
@endif
