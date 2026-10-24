<?php

namespace App\Livewire;

use App\Enums\AnomalyKind;
use App\Enums\AnomalyPriority;
use App\Enums\AnomalyStatus;
use App\Enums\PermissionName;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\Anomaly;
use App\Services\AnomalySync;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Anomali'])]
class Anomalies extends Component
{
    use AuthorizesShelf;

    #[Url]
    public string $status = '';

    #[Url]
    public string $kind = '';

    #[Url]
    public string $priority = '';

    public ?int $selectedId = null;

    public string $followStatus = 'new';

    public string $note = '';

    public string $ignoreReason = '';

    public string $message = '';

    public function mount(AnomalySync $anomalies): void
    {
        $anomalies->sync();
    }

    public function inspect(int $anomalyId): void
    {
        $anomaly = Anomaly::query()->findOrFail($anomalyId);
        $this->selectedId = $anomaly->id;
        $this->followStatus = $anomaly->status->value;
        $this->note = (string) $anomaly->note;
        $this->ignoreReason = (string) $anomaly->ignore_reason;
        $this->resetValidation();
    }

    public function closeInspect(): void
    {
        $this->selectedId = null;
        $this->resetValidation();
    }

    public function saveFollowUp(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::AnomaliesManage);
        $this->validate([
            'selectedId' => ['required', 'exists:anomalies,id'],
            'followStatus' => ['required', Rule::in(array_column(AnomalyStatus::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:1000'],
            'ignoreReason' => ['required_if:followStatus,ignored', 'nullable', 'string', 'max:255'],
        ], [
            'ignoreReason.required_if' => 'Alasan wajib diisi saat anomali diabaikan.',
        ]);

        $anomaly = Anomaly::query()->findOrFail($this->selectedId);
        $anomaly->fill([
            'status' => $this->followStatus,
            'note' => $this->note !== '' ? $this->note : null,
            'ignore_reason' => $this->followStatus === AnomalyStatus::Ignored->value ? $this->ignoreReason : null,
        ])->save();

        $audit->record('anomaly.updated', 'Tindak lanjut anomali #'.$anomaly->id.' disimpan.', $anomaly);
        $this->message = 'Catatan tindak lanjut disimpan.';
        $this->selectedId = null;
    }

    public function render(): View
    {
        $query = Anomaly::query()->with(['device.product', 'event']);

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }
        if ($this->kind !== '') {
            $query->where('kind', $this->kind);
        }
        if ($this->priority !== '') {
            $query->where('priority', $this->priority);
        }

        $anomalies = $query
            ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('livewire.anomalies', [
            'anomalies' => $anomalies,
            'selected' => $this->selectedId === null ? null : $anomalies->firstWhere('id', $this->selectedId) ?? Anomaly::query()->find($this->selectedId),
            'statuses' => AnomalyStatus::cases(),
            'kinds' => AnomalyKind::cases(),
            'priorities' => AnomalyPriority::cases(),
        ]);
    }
}
