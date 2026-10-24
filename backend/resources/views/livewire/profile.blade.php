<div class="page">
    <section class="card max-w-lg space-y-3">
        <h1 class="page-title">Profil</h1>
        <p class="page-desc">{{ $account->email }}</p>
        <p class="text-sm text-wd-ink">Peran: {{ $account->roles->pluck('display_name')->join(', ') ?: 'Tanpa peran' }}</p>
        <p class="text-sm text-wd-muted">Status: {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</p>
        @if ($message !== '')
            <div class="alert alert-info">{{ $message }}</div>
        @endif
        <label class="field-label" for="profile-name">Nama</label>
        <input id="profile-name" class="field-input" wire:model="name">
        @error('name') <div class="alert-error rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label" for="profile-current">Sandi saat ini</label>
        <input id="profile-current" class="field-input" type="password" wire:model="currentPassword" autocomplete="current-password">
        @error('currentPassword') <div class="alert-error rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label" for="profile-password">Sandi baru</label>
        <input id="profile-password" class="field-input" type="password" wire:model="password" autocomplete="new-password">
        @error('password') <div class="alert-error rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label" for="profile-password-confirmation">Ulangi sandi baru</label>
        <input id="profile-password-confirmation" class="field-input" type="password" wire:model="passwordConfirmation" autocomplete="new-password">
        <button class="btn btn-primary" type="button" wire:click="save">Simpan profil</button>
    </section>
</div>
