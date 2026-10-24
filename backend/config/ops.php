<?php

return [

    /*
    | Akun demo lokal. Ganti lewat env sebelum dipakai di luar mesin pengembangan.
    */
    'staff' => [
        'admin' => [
            'name' => env('ADMIN_NAME', 'Admin Ritelin'),
            'email' => env('ADMIN_EMAIL', 'admin@ritelin.local'),
            'password' => env('ADMIN_PASSWORD', 'password'),
            'role' => 'admin',
        ],
        'petugas' => [
            'name' => env('PETUGAS_NAME', 'Petugas Toko'),
            'email' => env('PETUGAS_EMAIL', 'petugas@ritelin.local'),
            'password' => env('PETUGAS_PASSWORD', 'password'),
            'role' => 'petugas',
        ],
        'pemilik' => [
            'name' => env('PEMILIK_NAME', 'Pemilik Toko'),
            'email' => env('PEMILIK_EMAIL', 'pemilik@ritelin.local'),
            'password' => env('PEMILIK_PASSWORD', 'password'),
            'role' => 'pemilik',
        ],
    ],

];
