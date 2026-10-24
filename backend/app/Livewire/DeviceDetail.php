<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Enums\SensorStatus;
use App\Enums\ShelfStatus;
use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\Device;
use App\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Detail perangkat'])]
class DeviceDetail extends Component
{
    use AuthorizesShelf;

    public Device $device;

    public string $storeLocation = '';

    public string $slotLabel = '';

    public string $baseline = '';

    public string $threshold = '';

    public string $shelfStatus = 'normal';

    public string $sensorStatus = 'unknown';

    public string $message = '';

    public function mount(Device $device): void
    {
        $this->device = $device->load('product');
        $this->fillForm();
    }

    public function save(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->validate($this->rules());

        $this->device->fill([
            'store_location' => $this->storeLocation !== '' ? $this->storeLocation : null,
            'slot_label' => $this->slotLabel !== '' ? $this->slotLabel : null,
            'baseline_weight_grams' => $this->baseline !== '' ? $this->baseline : null,
            'threshold_grams' => $this->threshold !== '' ? $this->threshold : null,
            'shelf_status' => $this->shelfStatus,
            'sensor_status' => $this->sensorStatus,
        ])->save();

        $audit->record('device.updated', 'Pengaturan slot '.$this->device->code.' diperbarui.', $this->device);
        $this->device->refresh();
        $this->message = 'Perubahan perangkat disimpan.';
    }

    public function toggleActive(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        $this->device->is_active = ! $this->device->is_active;
        $this->device->save();
        $audit->record(
            'device.activation',
            $this->device->code.' '.($this->device->is_active ? 'diaktifkan' : 'dinonaktifkan').'.',
            $this->device,
        );
        $this->message = $this->device->is_active ? 'Perangkat diaktifkan.' : 'Perangkat dinonaktifkan.';
    }

    public function calibrate(AuditLogger $audit): void
    {
        $this->authorizeShelf(PermissionName::DevicesManage);
        if ($this->device->last_weight_grams === null) {
            $this->addError('baseline', 'Belum ada pembacaan untuk dijadikan berat dasar.');

            return;
        }

        $this->device->forceFill([
            'baseline_weight_grams' => $this->device->last_weight_grams,
            'last_calibrated_at' => now(),
            'shelf_status' => ShelfStatus::Normal,
        ])->save();
        $audit->record('device.calibrated', 'Berat dasar '.$this->device->code.' dicatat dari pembacaan terakhir.', $this->device);
        $this->device->refresh();
        $this->fillForm();
        $this->message = 'Kalibrasi dicatat. Berat dasar mengikuti pembacaan terakhir.';
    }

    public function render(): View
    {
        return view('livewire.device-detail', [
            'events' => $this->device->events()->orderByDesc('occurred_at')->orderByDesc('id')->limit(15)->get(),
            'shelfStatuses' => ShelfStatus::cases(),
            'sensorStatuses' => SensorStatus::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'storeLocation' => ['nullable', 'string', 'max:80'],
            'slotLabel' => ['nullable', 'string', 'max:40'],
            'baseline' => $this->baseline === '' ? ['nullable'] : ['numeric', 'between:-5000,5000'],
            'threshold' => $this->threshold === '' ? ['nullable'] : ['numeric', 'between:1,5000'],
            'shelfStatus' => ['required', Rule::in(array_column(ShelfStatus::cases(), 'value'))],
            'sensorStatus' => ['required', Rule::in(array_column(SensorStatus::cases(), 'value'))],
        ];
    }

    private function fillForm(): void
    {
        $this->storeLocation = (string) $this->device->store_location;
        $this->slotLabel = (string) $this->device->slot_label;
        $this->baseline = $this->device->baseline_weight_grams === null ? '' : (string) $this->device->baseline_weight_grams;
        $this->threshold = $this->device->threshold_grams === null ? '' : (string) $this->device->threshold_grams;
        $this->shelfStatus = $this->device->shelf_status?->value ?? ShelfStatus::Normal->value;
        $this->sensorStatus = $this->device->sensor_status?->value ?? SensorStatus::Unknown->value;
    }
}
