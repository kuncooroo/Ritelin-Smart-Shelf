<?php

namespace App\Enums;

enum PermissionName: string
{
    case DevicesView = 'shelf.devices.view';
    case DevicesManage = 'shelf.devices.manage';
    case ProductsManage = 'shelf.products.manage';
    case EventsView = 'shelf.events.view';
    case SensorsView = 'shelf.sensors.view';
    case AnomaliesView = 'shelf.anomalies.view';
    case AnomaliesManage = 'shelf.anomalies.manage';
    case ReconciliationView = 'shelf.reconciliation.view';
    case ReportsView = 'shelf.reports.view';
    case SettingsManage = 'shelf.settings.manage';
    case PosManage = 'shelf.pos.manage';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';

    public function displayName(): string
    {
        return match ($this) {
            self::DevicesView => 'Melihat perangkat rak',
            self::DevicesManage => 'Mengelola perangkat rak',
            self::ProductsManage => 'Mengelola produk rak',
            self::EventsView => 'Melihat aktivitas rak',
            self::SensorsView => 'Melihat monitoring sensor',
            self::AnomaliesView => 'Melihat anomali rak',
            self::AnomaliesManage => 'Menindaklanjuti anomali rak',
            self::ReconciliationView => 'Melihat rekonsiliasi rak',
            self::ReportsView => 'Melihat laporan rak',
            self::SettingsManage => 'Mengubah pengaturan rak',
            self::PosManage => 'Mencatat transaksi kasir',
            self::UsersView => 'Melihat akun staf',
            self::UsersManage => 'Mengelola akun staf',
        };
    }
}
