<!DOCTYPE html>
<html lang="bn" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'KCAM') }} | কিন্ডারগার্টেন ক্লাউড একাডেমিক ম্যানেজমেন্ট</title>

    <!-- PWA & Mobile Icons -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#16a34a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="KCAM">

    

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-slate-900 font-sans antialiased pb-20 md:pb-0 flex flex-col md:flex-row bg-slate-50">

    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0 bg-white border-r border-slate-200 z-30">
        <div class="flex flex-col flex-grow pt-5 overflow-y-auto">
            <!-- Brand & School Info -->
            <div class="px-5 flex items-center justify-between pb-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold text-lg shadow-xs">
                        K
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 leading-tight">
                            {{ auth()->user()?->school?->name_bn ?? auth()->user()?->school?->name ?? 'কেসিএএম' }}
                        </h2>
                        <span class="inline-block text-[11px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded font-medium mt-0.5">
                            {{ auth()->user()?->role?->labelBn() ?? 'ব্যবহারকারী' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="mt-5 flex-1 px-3 space-y-1">
                @php
                    $role = auth()->user()?->role?->value ?? 'owner';
                @endphp

                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    <x-slot:icon>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </x-slot:icon>
                    ড্যাশবোর্ড
                </x-nav-link>

                @if ($role === 'owner' || $role === 'teacher')
                    @if (config('kcam.modules.attendance'))
                    <x-nav-link :href="route('attendance.index')" :active="request()->routeIs('attendance.*')">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </x-slot:icon>
                        হাজিরা
                    </x-nav-link>
                    @endif

                    @if (config('kcam.modules.students'))
                    <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </x-slot:icon>
                        শিক্ষার্থী তালিকা
                    </x-nav-link>
                    @endif
                @endif

                @if ($role === 'owner' || $role === 'accountant')
                    @if (config('kcam.modules.fees'))
                    <x-nav-link :href="route('fees.index')" :active="request()->routeIs('fees.*')">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </x-slot:icon>
                        ফি ও আদায়
                    </x-nav-link>
                    @endif
                @endif

                @if ($role === 'owner' || $role === 'teacher')
                    @if (config('kcam.modules.exams'))
                    <x-nav-link :href="route('exams.index')" :active="request()->routeIs('exams.*')">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </x-slot:icon>
                        পরীক্ষা ও ফলাফল
                    </x-nav-link>
                    @endif
                @endif

                @if (config('kcam.modules.notices'))
                <x-nav-link :href="route('notices.index')" :active="request()->routeIs('notices.*')">
                    <x-slot:icon>
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </x-slot:icon>
                    বিজ্ঞপ্তি ও এসএমএস
                </x-nav-link>
                @endif
            </nav>

            <!-- User Footer -->
            <div class="p-4 border-t border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 text-xs font-bold">
                        {{ mb_substr(auth()->user()?->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="truncate text-xs">
                        <p class="font-semibold text-slate-900 truncate">{{ auth()->user()?->name ?? 'User' }}</p>
                        <p class="text-slate-500 truncate">{{ auth()->user()?->email ?? '' }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg" title="লগআউট">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Mobile Header -->
    <header class="md:hidden sticky top-0 z-20 bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-sm">
                K
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-900 leading-tight">
                    {{ auth()->user()?->school?->name_bn ?? auth()->user()?->school?->name ?? 'কেসিএএম' }}
                </h1>
                <span class="text-[10px] text-slate-500 font-medium">
                    {{ auth()->user()?->role?->labelBn() ?? '' }}
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="p-2 text-slate-500 hover:text-slate-800 touch-target flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </button>
        </form>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 md:pl-64 flex flex-col min-h-screen">
        <div class="flex-1 px-4 py-5 sm:px-6 md:px-8 max-w-7xl w-full mx-auto">
            @if (isset($header))
                <div class="mb-6 pb-4 border-b border-slate-200/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    {{ $header }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>

    <!-- Mobile Bottom Navigation (Role-based, min 48px touch targets) -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 z-30 flex items-center justify-around h-16 px-1 safe-area-bottom">
        <a href="{{ route('dashboard') }}" class="flex-1 flex flex-col items-center justify-center py-1 touch-target {{ request()->routeIs('dashboard') ? 'text-emerald-700 font-bold' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="text-[11px] mt-1">হোম</span>
        </a>

        @if ($role === 'owner' || $role === 'teacher')
            @if (config('kcam.modules.attendance'))
            <a href="{{ route('attendance.index') }}" class="flex-1 flex flex-col items-center justify-center py-1 touch-target {{ request()->routeIs('attendance.*') ? 'text-emerald-700 font-bold' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span class="text-[11px] mt-1">হাজিরা</span>
            </a>
            @endif

            @if (config('kcam.modules.students'))
            <a href="{{ route('students.index') }}" class="flex-1 flex flex-col items-center justify-center py-1 touch-target {{ request()->routeIs('students.*') ? 'text-emerald-700 font-bold' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-[11px] mt-1">শিক্ষার্থী</span>
            </a>
            @endif
        @endif

        @if ($role === 'owner' || $role === 'accountant')
            @if (config('kcam.modules.fees'))
            <a href="{{ route('fees.index') }}" class="flex-1 flex flex-col items-center justify-center py-1 touch-target {{ request()->routeIs('fees.*') ? 'text-emerald-700 font-bold' : 'text-slate-500' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="text-[11px] mt-1">ফি</span>
            </a>
            @endif
        @endif

        @if (config('kcam.modules.notices'))
        <a href="{{ route('notices.index') }}" class="flex-1 flex flex-col items-center justify-center py-1 touch-target {{ request()->routeIs('notices.*') ? 'text-emerald-700 font-bold' : 'text-slate-500' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="text-[11px] mt-1">বিজ্ঞপ্তি</span>
        </a>
        @endif
    </nav>

    <!-- Global Toast Component -->
    <x-toast />

    @livewireScripts

    <!-- PWA Service Worker Registration -->
    <script>
        // Phones are shared: drop every cached asset when the user logs out.
        document.querySelectorAll('form[action$="/logout"]').forEach((form) => {
            form.addEventListener('submit', () => {
                if ('caches' in window) {
                    caches.keys().then((names) => names.forEach((name) => caches.delete(name)));
                }
            });
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch((err) => {
                    console.log('SW registration failed:', err);
                });
            });
        }
    </script>
</body>
</html>
