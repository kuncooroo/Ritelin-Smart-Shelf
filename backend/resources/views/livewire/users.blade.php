<div class="page space-y-6">
    <div>
        <h1 class="page-title">Pengguna</h1>
        <p class="page-desc">Akun staf dan peran pada dashboard rak.</p>
    </div>
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif
    @error('users') <div class="alert alert-error">{{ $message }}</div> @enderror

    <section class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Status</th>
                        @if (auth()->user()->hasPermission(\App\Enums\PermissionName::UsersManage))
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $account)
                        <tr>
                            <td>{{ $account->name }}</td>
                            <td>{{ $account->email }}</td>
                            <td>{{ $account->roles->pluck('display_name')->join(', ') ?: 'Tanpa peran' }}</td>
                            <td>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            @if (auth()->user()->hasPermission(\App\Enums\PermissionName::UsersManage))
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn" type="button" wire:click="openUser({{ $account->id }})" title="Ubah peran" aria-label="Ubah peran {{ $account->name }}">
                                            <x-icon name="pencil" />
                                        </button>
                                        <button class="icon-btn" type="button" wire:click="setActive({{ $account->id }}, {{ $account->is_active ? 'false' : 'true' }})" title="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $account->name }}">
                                            <x-icon name="{{ $account->is_active ? 'power' : 'check' }}" />
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @php $editingAccount = $users->firstWhere('id', $editingUserId); @endphp
    <x-modal :show="$editingAccount !== null" :title="$editingAccount ? 'Peran '.$editingAccount->name : 'Peran'" close="closeUser">
        @if ($editingAccount)
            <label class="field-label" for="user-role">Peran</label>
            <select id="user-role" class="field-select" wire:model="role">
                @foreach ($roles as $item)
                    <option value="{{ $item->name }}">{{ $item->display_name }}</option>
                @endforeach
            </select>
            @error('role') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
            <button class="btn btn-primary mt-3" type="button" wire:click="saveRole">Simpan peran</button>
        @endif
    </x-modal>
</div>
