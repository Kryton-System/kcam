<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ __('common.offline.title') }} | {{ __('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex items-center justify-center p-6 text-center font-sans">
    <div class="max-w-md w-full bg-white rounded-lg p-8 border border-slate-200 shadow-sm">
        <div class="w-16 h-16 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 4.243a9 9 0 01-12.728 0m0 0l2.829-2.829m-2.829 2.829L3 21m2.828-15.536a9 9 0 0112.728 0m-8.485 5.657a5 5 0 017.071 0" />
            </svg>
        </div>

        <h1 class="text-xl font-bold text-slate-900 mb-2">{{ __('common.offline.heading') }}</h1>
        <p class="text-sm text-slate-600 mb-6">
            {{ __('common.offline.message') }}
        </p>

        <button onclick="window.location.reload()" class="w-full py-3 px-4 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 touch-target transition-colors">
            {{ __('common.offline.retry') }}
        </button>
    </div>
</body>
</html>
