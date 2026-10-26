<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Livewire\Concerns\ConfirmsActions;
use App\Livewire\Concerns\WithPageSize;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Pengaturan'])]
class Settings extends Component
{
    use AuthorizesShelf;
    use ConfirmsActions;
    use WithPageSize;
    use WithPagination;

    public string $offlineSeconds = '';

    public string $thresholdGrams = '';

    public string $matchLeadMinutes = '';

    public string $matchWindowMinutes = '';

    public string $stockWindowMinutes = '';

    public string $message = '';

    public function mount(): void
    {
        $this->offlineSeconds = (string) AppSetting::offlineSeconds();
        $this->thresholdGrams = (string) AppSetting::thresholdGrams();
        $this->matchLeadMinutes = (string) AppSetting::matchLeadMinutes();
        $this->matchWindowMinutes = (string) AppSetting::matchWindowMinutes();
        $this->stockWindowMinutes = (string) AppSetting::stockWindowMinutes();
    }

    public function save(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::SettingsManage);
        $this->validate([
            'offlineSeconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'thresholdGrams' => ['required', 'numeric', 'min:1', 'max:5000'],
            'matchLeadMinutes' => ['required', 'integer', 'min:0', 'max:60'],
            'matchWindowMinutes' => ['required', 'integer', 'min:1', 'max:180'],
            'stockWindowMinutes' => ['required', 'integer', 'min:1', 'max:180'],
        ], [
            'offlineSeconds.min' => 'Batas offline paling singkat 30 detik.',
            'offlineSeconds.max' => 'Batas offline paling lama 3600 detik.',
            'matchLeadMinutes.min' => 'Menit sebelum pengambilan paling kecil 0.',
            'matchWindowMinutes.min' => 'Menit sesudah pengambilan paling kecil 1.',
            'stockWindowMinutes.min' => 'Menit tunggu stok paling kecil 1.',
        ]);

        $beforeOffline = AppSetting::offlineSeconds();
        $beforeThreshold = AppSetting::thresholdGrams();
        $beforeLead = AppSetting::matchLeadMinutes();
        $beforeWindow = AppSetting::matchWindowMinutes();
        $beforeStock = AppSetting::stockWindowMinutes();
        AppSetting::putValue('offline_seconds', (string) $this->offlineSeconds);
        AppSetting::putValue('threshold_grams', (string) $this->thresholdGrams);
        AppSetting::putValue('match_lead_minutes', (string) $this->matchLeadMinutes);
        AppSetting::putValue('match_window_minutes', (string) $this->matchWindowMinutes);
        AppSetting::putValue('stock_window_minutes', (string) $this->stockWindowMinutes);

        if ((int) $this->offlineSeconds !== $beforeOffline
            || (float) $this->thresholdGrams !== $beforeThreshold
            || (int) $this->matchLeadMinutes !== $beforeLead
            || (int) $this->matchWindowMinutes !== $beforeWindow
            || (int) $this->stockWindowMinutes !== $beforeStock) {
            $audit->record(
                'settings.updated',
                'Batas offline '.$beforeOffline.' menjadi '.$this->offlineSeconds.' detik. Ambang bawaan '.$beforeThreshold.' menjadi '.$this->thresholdGrams.' gram. Jendela kasir '.$beforeLead.'/'.$beforeWindow.' menjadi '.$this->matchLeadMinutes.'/'.$this->matchWindowMinutes.' menit. Tunggu stok '.$beforeStock.' menjadi '.$this->stockWindowMinutes.' menit.',
            );
        }

        $this->message = 'Pengaturan disimpan.';
    }

    protected function pageNames(): array
    {
        return ['logs'];
    }

    /**
     * @return list<string>
     */
    protected function confirmableActions(): array
    {
        return ['save'];
    }

    public function render(): View
    {
        return view('livewire.settings', [
            'logs' => AuditLog::query()->with('user')->orderByDesc('id')->paginate($this->pageSize(), ['*'], 'logs'),
            'posReady' => config('rakku.pos_token') !== null && config('rakku.pos_token') !== '',
        ]);
    }
}
