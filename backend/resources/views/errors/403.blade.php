@extends('layouts.app', ['title' => 'Akses ditolak'])

@section('content')
    <div class="page">
        <section class="card">
            <h1 class="page-title">Akses ditolak</h1>
            <p class="page-desc">Peran akun ini tidak mencakup halaman tersebut.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary mt-4">Kembali ke beranda</a>
        </section>
    </div>
@endsection
