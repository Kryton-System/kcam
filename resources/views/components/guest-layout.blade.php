<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'KCAM') }} | {{ __('app.tagline') }}</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#16a34a">

    <!-- Fonts: Inter for English, Hind Siliguri for Bangla -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap">


    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-slate-900 font-sans antialiased bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 flex justify-end">
        <x-language-switcher />
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center px-4">
        <div class="inline-flex w-14 h-14 rounded-lg bg-emerald-600 items-center justify-center text-white font-extrabold text-2xl shadow-sm mb-3">
            K
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
            {{ __('app.name') }}
        </h2>
        <p class="mt-1 text-sm text-slate-600 font-medium">
            {{ __('messages.tagline') }}
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 shadow-sm rounded-lg sm:px-10 border border-slate-200/80">
            {{ $slot }}
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} KCAM - {{ __('app.tagline') }}
        </div>
    </div>

    <x-toast />
    @livewireScripts
</body>
</html>
