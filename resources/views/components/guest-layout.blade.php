<!DOCTYPE html>
<html lang="bn" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'KCAM') }} | কিন্ডারগার্টেন ক্লাউড একাডেমিক ম্যানেজমেন্ট</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#16a34a">

    <!-- Fonts -->

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-slate-900 font-sans antialiased bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center px-4">
        <div class="inline-flex w-14 h-14 rounded-2xl bg-emerald-600 items-center justify-center text-white font-extrabold text-2xl shadow-md mb-3">
            K
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
            কেসিএএম
        </h2>
        <p class="mt-1 text-sm text-slate-600 font-medium">
            আপনার পুরো স্কুল, আপনার ফোনে।
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 shadow-sm rounded-2xl sm:px-10 border border-slate-200/80">
            {{ $slot }}
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} KCAM - কিন্ডারগার্টেন ক্লাউড একাডেমিক ম্যানেজমেন্ট
        </div>
    </div>

    <x-toast />
    @livewireScripts
</body>
</html>
