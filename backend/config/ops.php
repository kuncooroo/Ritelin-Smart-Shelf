<?php

return [

    /*
    | Akun utama. Dipakai MainSeeder. Akun yang sudah ada tidak diganti sandinya.
    */
    'primary' => [
        'name' => env('SUPERADMIN_NAME', env('ADMIN_NAME', 'Admin Rakku')),
        'email' => env('SUPERADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@ritelin.local')),
        'password' => env('SUPERADMIN_PASSWORD', env('ADMIN_PASSWORD', 'password')),
        'role' => 'superadmin',
    ],

    /*
    | Akun demo lokal. Hanya DemoSeeder. Jangan dijalankan di data toko yang sudah dipakai.
    */
    'demo' => [
        'admin' => [
            'name' => 'Admin toko',
            'email' => 'admin.toko@ritelin.local',
            'password' => 'password',
            'role' => 'admin',
        ],
        'petugas' => [
            'name' => env('PETUGAS_NAME', 'Petugas Toko'),
            'email' => env('PETUGAS_EMAIL', 'petugas@ritelin.local'),
            'password' => env('PETUGAS_PASSWORD', 'password'),
            'role' => 'staff',
        ],
        'pemilik' => [
            'name' => env('PEMILIK_NAME', 'Pemilik Toko'),
            'email' => env('PEMILIK_EMAIL', 'pemilik@ritelin.local'),
            'password' => env('PEMILIK_PASSWORD', 'password'),
            'role' => 'staff',
        ],
        'kasir' => [
            'name' => 'Kasir demo',
            'email' => 'kasir.demo@ritelin.local',
            'password' => 'password',
            'role' => 'staff',
        ],
        'gudang' => [
            'name' => 'Staf gudang',
            'email' => 'gudang@ritelin.local',
            'password' => 'password',
            'role' => 'staff',
        ],
    ],

];
