<div wire:loading.delay.shortest wire:target="runConfirmed" class="alert alert-info">Aksi sedang dijalankan. Tunggu sampai selesai.</div>

@if ($confirmOpen)
    <div class="modal-backdrop-front" wire:click.self="cancelConfirm" wire:keydown.escape.window="cancelConfirm">
        <div class="modal-panel" role="dialog" aria-modal="true" aria-label="{{ $confirmTitle !== '' ? $confirmTitle : 'Setujui aksi' }}">
            <h2 class="text-base font-semibold">{{ $confirmTitle !== '' ? $confirmTitle : 'Setujui aksi' }}</h2>
            <p class="mt-3 text-sm">{{ $confirmText }}</p>
            <p class="mt-2 text-xs text-wd-muted">Proses belum dijalankan. Pilih setujui untuk melanjutkan.</p>
            <div class="mt-4 flex gap-2">
                <button class="btn btn-primary" type="button" wire:click="runConfirmed" wire:loading.attr="disabled" wire:target="runConfirmed">
                    <span wire:loading.remove wire:target="runConfirmed">Setujui</span>
                    <span wire:loading wire:target="runConfirmed">Memproses...</span>
                </button>
                <button class="btn" type="button" wire:click="cancelConfirm" wire:loading.attr="disabled" wire:target="runConfirmed">Batal</button>
            </div>
        </div>
    </div>
@endif
