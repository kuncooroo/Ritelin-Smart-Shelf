<?php

namespace App\Enums;

enum RoleName: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Staff = 'staff';

    public function displayName(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Admin',
            self::Staff => 'Staf',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Superadmin => 'Peran tertinggi. Satu-satunya peran yang boleh membuat, mengubah, dan menghapus akun, plus seluruh pengelolaan rak.',
            self::Admin => 'Mengelola perangkat, produk, kasir, anomali, dan pengaturan. Tidak mengelola akun.',
            self::Staff => 'Memantau perangkat, aktivitas rak, anomali, rekonsiliasi, dan laporan.',
        };
    }
}
