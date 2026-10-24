<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Pemilik = 'pemilik';

    public function displayName(): string
    {
        return match ($this) {
            self::Admin => 'Admin retail',
            self::Petugas => 'Petugas toko',
            self::Pemilik => 'Pemilik toko',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Mengelola perangkat, produk, transaksi kasir, dan akun staf.',
            self::Petugas => 'Memantau perangkat dan aktivitas rak.',
            self::Pemilik => 'Melihat kondisi rak dan indikasi selisih stok.',
        };
    }
}
