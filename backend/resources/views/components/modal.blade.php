@props(['show' => false, 'title', 'close'])

@if ($show)
    <div class="modal-backdrop" wire:click.self="{{ $close }}" wire:keydown.escape.window="{{ $close }}">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-label="{{ $title }}">
            <div class="mb-4 flex items-start justify-between gap-3">
                <h2 class="text-base font-semibold">{{ $title }}</h2>
                <button type="button" class="icon-btn" wire:click="{{ $close }}" aria-label="Tutup">
                    <x-icon name="x" />
                </button>
            </div>
            {{ $slot }}
        </div>
    </div>
@endif
