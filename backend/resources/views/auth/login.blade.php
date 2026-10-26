@extends('layouts.guest')

@section('content')
<div class="mb-8 text-center">
    <h1 class="text-3xl font-bold tracking-tight text-white drop-shadow-md">
        <img src="{{ asset('images/logo.png') }}"
            alt="{{ config('app.name') }}"
            class="h-10 w-auto mx-auto">
    </h1>
    <p class="mt-2 text-sm text-amber-100/80">
        Masuk dashboard rak. Tidak ada registrasi publik.
    </p>
</div>

@if ($errors->any())
<div class="mb-6 rounded-xl border border-red-500/30 bg-red-500/20 p-4 backdrop-blur-md" role="alert">
    <p class="text-sm font-medium text-red-200">
        {{ $errors->first() }}
    </p>
</div>
@endif

<form method="POST" action="{{ route('login.store') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="mb-2 block text-sm font-medium text-white">
            Email
        </label>
        <input
            id="email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="nama@email.com"
            required
            autofocus
            autocomplete="username"
            class="w-full rounded-xl border border-white/20 bg-white px-4 py-3 text-gray-900 outline-none shadow-sm transition placeholder:text-gray-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/50">
    </div>

    <div>
        <label for="password" class="mb-2 block text-sm font-medium text-white">
            Password
        </label>
        <input
            id="password"
            type="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="current-password"
            class="w-full rounded-xl border border-white/20 bg-white px-4 py-3 text-gray-900 outline-none shadow-sm transition placeholder:text-gray-400 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/50">
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2.5 text-sm text-amber-100 cursor-pointer select-none">
            <input
                type="checkbox"
                name="remember"
                value="1"
                class="h-4 w-4 rounded border-gray-300 text-amber-500 accent-amber-500 focus:ring-amber-400"
                @checked(old('remember'))>
            <span>Ingat saya</span>
        </label>
    </div>

    <button
        type="submit"
        class="w-full rounded-xl bg-amber-500 py-3.5 text-sm font-bold text-gray-950 shadow-lg shadow-amber-500/30 transition hover:bg-amber-400 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-amber-400">
        Masuk
    </button>
</form>
@endsection