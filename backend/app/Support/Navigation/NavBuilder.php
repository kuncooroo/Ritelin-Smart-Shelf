<?php

namespace App\Support\Navigation;

use App\Models\User;
use App\Support\Authorization\NavPermissionMap;
use Illuminate\Support\Facades\Route;

final class NavBuilder
{
    /**
     * @return list<array{group: string, items: list<array<string, mixed>>}>
     */
    public function grouped(User $user): array
    {
        $groups = [];

        foreach ($this->catalog() as $group => $items) {
            $visible = [];

            foreach ($items as $item) {
                if (! NavPermissionMap::canSee($user, $item['key'])) {
                    continue;
                }

                $visible[] = $this->hydrate($item);
            }

            if ($visible !== []) {
                $groups[] = [
                    'group' => $group,
                    'items' => $visible,
                ];
            }
        }

        return $groups;
    }

    /**
     * @return array<string, list<array{key: string, label: string, route: string, active?: string}>>
     */
    private function catalog(): array
    {
        return [
            'Operasi' => [
                ['key' => 'home', 'label' => 'Beranda', 'route' => 'dashboard'],
                ['key' => 'devices', 'label' => 'Perangkat', 'route' => 'dashboard.devices', 'active' => 'dashboard.devices*'],
                ['key' => 'products', 'label' => 'Produk & rak', 'route' => 'dashboard.products'],
                ['key' => 'sensors', 'label' => 'Monitoring sensor', 'route' => 'dashboard.sensors'],
                ['key' => 'events', 'label' => 'Riwayat aktivitas', 'route' => 'dashboard.events'],
                ['key' => 'anomalies', 'label' => 'Anomali', 'route' => 'dashboard.anomalies'],
                ['key' => 'pos', 'label' => 'Transaksi kasir', 'route' => 'dashboard.pos'],
                ['key' => 'reconciliation', 'label' => 'Rekonsiliasi', 'route' => 'dashboard.reconciliation'],
                ['key' => 'reports', 'label' => 'Laporan', 'route' => 'dashboard.reports'],
            ],
            'Administrasi' => [
                ['key' => 'settings', 'label' => 'Pengaturan', 'route' => 'dashboard.settings'],
                ['key' => 'users', 'label' => 'Pengguna', 'route' => 'dashboard.users'],
            ],
        ];
    }

    /**
     * @param  array{key: string, label: string, route: string, active?: string}  $item
     * @return array<string, mixed>
     */
    private function hydrate(array $item): array
    {
        $route = $item['route'];
        $url = Route::has($route) ? route($route) : null;

        return [
            'key' => $item['key'],
            'label' => $item['label'],
            'url' => $url,
            'active' => $url !== null && request()->routeIs($item['active'] ?? $route),
        ];
    }
}
