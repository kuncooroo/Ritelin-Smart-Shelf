<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Pengaturan'])]
class Settings extends Component
{
    use AuthorizesShelf;

    public string $offlineSeconds = '';

    public string $thresholdGrams = '';

    public string $message = '';

    public function mount(): void
    {
        $this->offlineSeconds = (string) AppSetting::offlineSeconds();
        $this->thresholdGrams = (string) AppSetting::thresholdGrams();
    }

    public function save(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::SettingsManage);
        $this->validate([
            'offlineSeconds' => ['required', 'integer', 'min:30', 'max:3600'],
            'thresholdGrams' => ['required', 'numeric', 'min:1', 'max:5000'],
        ], [
            'offlineSeconds.min' => 'Batas offline paling singkat 30 detik.',
            'offlineSeconds.max' => 'Batas offline paling lama 3600 detik.',
        ]);

        $beforeOffline = AppSetting::offlineSeconds();
        $beforeThreshold = AppSetting::thresholdGrams();
        AppSetting::putValue('offline_seconds', (string) $this->offlineSeconds);
        AppSetting::putValue('threshold_grams', (string) $this->thresholdGrams);

        if ((int) $this->offlineSeconds !== $beforeOffline || (float) $this->thresholdGrams !== $beforeThreshold) {
            $audit->record(
                'settings.updated',
                'Batas offline '.$beforeOffline.' menjadi '.$this->offlineSeconds.' detik. Ambang bawaan '.$beforeThreshold.' menjadi '.$this->thresholdGrams.' gram.',
            );
        }

        $this->message = 'Pengaturan disimpan.';
    }

    public function render(): View
    {
        return view('livewire.settings', [
            'logs' => AuditLog::query()->with('user')->orderByDesc('id')->limit(30)->get(),
            'posReady' => config('ritelin.pos_token') !== null && config('ritelin.pos_token') !== '',
        ]);
    }
}
