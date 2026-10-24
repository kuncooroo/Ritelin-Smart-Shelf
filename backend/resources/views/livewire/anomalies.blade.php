<div class="page space-y-6">
    <div>
        <h1 class="page-title">Anomali</h1>
        <p class="page-desc">Kejadian yang perlu diperiksa staf. Perubahan berat saja tidak membuktikan penyebabnya.</p>
    </div>
    <x-shelf-disclaimer />
    @if ($message !== '')
        <div class="alert alert-info">{{ $message }}</div>
    @endif

    <section class="card">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label class="field-label" for="anomaly-status">Status</label>
                <select id="anomaly-status" class="field-select" wire:model.live="status">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="anomaly-kind">Jenis</label>
                <select id="anomaly-kind" class="field-select" wire:model.live="kind">
                    <option value="">Semua jenis</option>
                    @foreach ($kinds as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="anomaly-priority">Prioritas</label>
                <select id="anomaly-priority" class="field-select" wire:model.live="priority">
                    <option value="">Semua prioritas</option>
                    @foreach ($priorities as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    @if ($selected)
        <x-modal :show="true" :title="$selected->title" close="closeInspect">
            <p class="text-sm">{{ $selected->summary }}</p>
            @if (auth()->user()->hasPermission(\App\Enums\PermissionName::AnomaliesManage))
                <label class="field-label mt-3" for="follow-status">Status pemeriksaan</label>
                <select id="follow-status" class="field-select" wire:model="followStatus">
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
                <label class="field-label mt-3" for="follow-note">Catatan tindak lanjut</label>
                <textarea id="follow-note" class="field-input" rows="3" wire:model="note"></textarea>
                <label class="field-label mt-3" for="ignore-reason">Alasan jika diabaikan</label>
                <input id="ignore-reason" class="field-input" wire:model="ignoreReason">
                @error('ignoreReason') <div class="alert-error mt-2 rounded-lg px-3 py-2">{{ $message }}</div> @enderror
                <button class="btn btn-primary mt-3" type="button" wire:click="saveFollowUp">Simpan tindak lanjut</button>
            @else
                <p class="mt-3 text-sm text-wd-muted">Catatan: {{ $selected->note ?: 'Belum ada' }}</p>
            @endif
        </x-modal>
    @endif

    <section class="card">
        <div class="table-wrap">
            <table class="table table-wide">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Perangkat</th>
                        <th>Jenis</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($anomalies as $anomaly)
                        <tr>
                            <td>
                                {{ $anomaly->title }}
                                <div class="text-xs text-wd-muted">{{ $anomaly->summary }}</div>
                            </td>
                            <td>{{ $anomaly->device->code ?? '—' }}</td>
                            <td>{{ $anomaly->kind->label() }}</td>
                            <td>{{ $anomaly->priority->label() }}</td>
                            <td>
                                <span class="pill">{{ $anomaly->status->label() }}</span>
                                @if ($anomaly->ignore_reason)
                                    <div class="text-xs text-wd-muted">{{ $anomaly->ignore_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <button class="icon-btn" type="button" wire:click="inspect({{ $anomaly->id }})" title="Periksa" aria-label="Periksa anomali {{ $anomaly->id }}">
                                    <x-icon name="search" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Belum ada anomali yang perlu diperiksa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
