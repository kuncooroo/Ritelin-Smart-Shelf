@extends('layouts.guest')

@section('content')
<div class="mb-8 text-center">
    <h1 class="text-3xl font-bold tracking-tight text-white drop-shadow-md">{{ config('app.name') }}</h1>
    <p class="mt-2 text-sm text-gray-200/80">Masuk dashboard rak. Tidak ada registrasi publik.</p>
</div>

@if ($errors->any())
    <div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/20 p-4 backdrop-blur-md" role="alert">
        <p class="text-sm font-medium text-red-200">{{ $errors->first() }}</p>
    </div>
@endif

<form method="POST" action="{{ route('login.store') }}" class="space-y-5">
    @csrf
    <div>
        <label for="email" class="mb-2 block text-sm font-medium text-gray-100">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus autocomplete="username" class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white outline-none placeholder-gray-300/60 backdrop-blur-md transition focus:border-emerald-400 focus:bg-white/20 focus:ring-4 focus:ring-emerald-400/20">
    </div>
    <div>
        <label for="password" class="mb-2 block text-sm font-medium text-gray-100">Password</label>
        <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white outline-none placeholder-gray-300/60 backdrop-blur-md transition focus:border-emerald-400 focus:bg-white/20 focus:ring-4 focus:ring-emerald-400/20">
    </div>
    <label class="flex items-center gap-2.5 text-sm text-gray-200">
        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/30 bg-white/10 text-emerald-500" @checked(old('remember'))>
        <span>Ingat saya</span>
    </label>
    <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:from-emerald-600 hover:to-teal-700">Masuk</button>
</form>
@endsection
