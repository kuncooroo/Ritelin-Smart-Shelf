<div class="page space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="page-title">{{ $bagian === 'peran' ? 'Peran' : 'Pengguna' }}</h1>
            <p class="page-desc">
                @if ($bagian === 'peran')
                    Hak akses tiap peran. Centang yang boleh dilakukan. Hanya Superadmin, peran tertinggi, yang dapat mengubahnya.
                @else
                    Akun staf. Hanya Superadmin, peran tertinggi, yang boleh membuat, mengubah, dan menghapus akun.
                @endif
            </p>
        </div>
        @if ($canManage && $bagian === 'pengguna')
            <button class="icon-btn" type="button" wire:click="openCreate" title="Pengguna baru" aria-label="Pengguna baru">
                <x-icon name="plus" />
            </button>
        @endif
    </div>

    <div class="flex gap-2" role="tablist">
        <button class="btn {{ $bagian === 'pengguna' ? 'btn-primary' : '' }}" type="button" role="tab" aria-selected="{{ $bagian === 'pengguna' ? 'true' : 'false' }}" wire:click="$set('bagian', 'pengguna')">Pengguna</button>
        <button class="btn {{ $bagian === 'peran' ? 'btn-primary' : '' }}" type="button" role="tab" aria-selected="{{ $bagian === 'peran' ? 'true' : 'false' }}" wire:click="$set('bagian', 'peran')">Peran</button>
    </div>

    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif
    @error('users') <div class="alert alert-error">{{ $message }}</div> @enderror
    @error('roles') <div class="alert alert-error">{{ $message }}</div> @enderror
    @include('components.action-confirm')

    @if ($bagian === 'peran')
        <section class="card">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Hak akses</th>
                            @foreach ($roles as $item)
                                <th class="text-center">
                                    <div>{{ $item['label'] }}</div>
                                    @if ($item['highest'])
                                        <span class="pill mt-1">Peran tertinggi</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td>{{ $permission->displayName() }}</td>
                                @foreach ($roles as $item)
                                    @php
                                        $locked = $item['highest'] && in_array($permission, [\App\Enums\PermissionName::UsersView, \App\Enums\PermissionName::UsersManage], true);
                                        $checked = in_array($permission->value, $item['granted'], true);
                                    @endphp
                                    <td class="text-center">
                                        <input
                                            type="checkbox"
                                            @checked($checked)
                                            @disabled(! $canManage || $locked)
                                            wire:click.prevent="ask('togglePermission', 'Ubah hak akses', 'Setujui perubahan hak {{ $permission->displayName() }} untuk {{ $item['label'] }}?', { roleName: '{{ $item['name'] }}', permissionName: '{{ $permission->value }}' })"
                                            aria-label="{{ $item['label'] }}: {{ $permission->displayName() }}"
                                            title="{{ $locked ? 'Hak ini tetap pada peran tertinggi.' : $item['description'] }}"
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
    <section class="card">
        <label class="field-label" for="user-search">Cari</label>
        <input id="user-search" class="field-input mb-4 max-w-md" wire:model.live.debounce.300ms="search" placeholder="Nama atau email">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        @if ($canManage)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $account)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->email }}</td>
                            <td>{{ $account->roles->pluck('display_name')->join(', ') ?: 'Tanpa peran' }}</td>
                            <td>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            @if ($canManage)
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn" type="button" wire:click="openUser({{ $account->id }})" title="Ubah" aria-label="Ubah {{ $account->name }}">
                                            <x-icon name="pencil" />
                                        </button>
                                        <button class="icon-btn" type="button" wire:click="ask('setActive', '{{ $account->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}', 'Setujui perubahan status {{ $account->name }} sebelum status diubah.', { userId: {{ $account->id }}, active: {{ $account->is_active ? 'false' : 'true' }} })" title="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $account->name }}">
                                            <x-icon name="{{ $account->is_active ? 'power' : 'check' }}" />
                                        </button>
                                        <button class="icon-btn" type="button" wire:click="openDelete({{ $account->id }})" title="Hapus" aria-label="Hapus {{ $account->name }}">
                                            <x-icon name="trash" />
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6">{{ $search === '' ? 'Belum ada pengguna.' : 'Tidak ada pengguna yang cocok.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :rows="$users" />
    </section>
    @endif

    <x-modal :show="$showForm" :title="$editingUserId ? 'Ubah pengguna' : 'Pengguna baru'" close="closeUser">
        <label class="field-label" for="user-name">Nama</label>
        <input id="user-name" class="field-input" wire:model="name">
        @error('name') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="user-email">Email</label>
        <input id="user-email" class="field-input" type="email" wire:model="email">
        @error('email') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="user-password">Kata sandi</label>
        <input id="user-password" class="field-input" type="password" wire:model="password" autocomplete="new-password">
        <p class="mt-1 text-xs text-wd-muted">{{ $editingUserId ? 'Kosongkan jika kata sandi tidak diubah.' : 'Paling sedikit 8 karakter.' }}</p>
        @error('password') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="field-label mt-3" for="user-role">Peran</label>
        <select id="user-role" class="field-select" wire:model="role">
            @foreach (\App\Enums\RoleName::cases() as $item)
                <option value="{{ $item->value }}">{{ $item->displayName() }}</option>
            @endforeach
        </select>
        @error('role') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
        <label class="mt-3 flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="isActive"> Akun aktif
        </label>
        <button class="btn btn-primary mt-4" type="button" wire:click="ask('saveUser', 'Simpan akun', 'Setujui penyimpanan akun ini?')">Simpan</button>
    </x-modal>

    <x-modal :show="$deletingAccount !== null" :title="$deletingAccount ? 'Hapus '.$deletingAccount->name : 'Hapus pengguna'" close="closeDelete">
        @if ($deletingAccount)
            <p class="text-sm">Setujui penghapusan sebelum akun dihapus. Akun {{ $deletingAccount->email }} akan dihapus.</p>
            <div wire:loading wire:target="deleteUser" class="alert alert-info mt-3">Penghapusan sedang dijalankan.</div>
            <div class="mt-4 flex gap-2">
                <button class="btn btn-primary" type="button" wire:click="deleteUser" wire:loading.attr="disabled" wire:target="deleteUser">
                    <span wire:loading.remove wire:target="deleteUser">Hapus</span>
                    <span wire:loading wire:target="deleteUser">Menghapus...</span>
                </button>
                <button class="btn" type="button" wire:click="closeDelete">Batal</button>
            </div>
        @endif
    </x-modal>
</div>
