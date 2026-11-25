<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('app.name') }} | {{ __('home.title') }}</title>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#059669">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased selection:bg-emerald-100 selection:text-emerald-900">
    <!-- Top Navigation -->
    <header class="sticky top-0 z-40 bg-white/90  border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-extrabold text-lg shadow-xs">
                    K
                </div>
                <div class="leading-tight">
                    <span class="block text-base font-bold tracking-tight text-slate-900">{{ __('app.name') }}</span>
                    <span class="hidden sm:block text-[11px] font-medium text-slate-500">{{ __('app.tagline') }}</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#features" class="hover:text-emerald-700 transition-colors">{{ __('home.nav.features') }}</a>
                <a href="#modules" class="hover:text-emerald-700 transition-colors">{{ __('home.nav.modules') }}</a>
                <a href="#roles" class="hover:text-emerald-700 transition-colors">{{ __('home.nav.roles') }}</a>
            </nav>

            <div class="flex items-center gap-2.5 sm:gap-3">
                <x-language-switcher />

                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors shadow-xs">
                        {{ __('home.nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-colors shadow-xs">
                        {{ __('home.nav.login') }}
                    </a>

                    @if (config('kcam.mode') !== 'single' && Route::has('register'))
                        <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors">
                            {{ __('home.nav.register') }}
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-slate-50 from-white   border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 lg:py-24">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                    <div class="lg:col-span-6 space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-semibold">
                            <span class="w-2 h-2 rounded-lg bg-emerald-600"></span>
                            {{ __('home.hero.badge') }}
                        </div>

                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                            {{ __('home.hero.heading') }}
                        </h1>

                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl">
                            {{ __('home.hero.subheading') }}
                        </p>

                        <div class="flex flex-wrap items-center gap-3 pt-2">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-all shadow-sm">
                                    {{ __('home.hero.cta_dashboard') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-all shadow-sm">
                                    {{ __('home.hero.cta_primary') }}
                                </a>

                                @if (config('kcam.mode') !== 'single' && Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-slate-300 bg-white text-slate-800 text-sm font-semibold hover:bg-slate-50 transition-all">
                                        {{ __('home.hero.cta_register') }}
                                    </a>
                                @else
                                    <a href="#modules" class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-slate-300 bg-white text-slate-800 text-sm font-semibold hover:bg-slate-50 transition-all">
                                        {{ __('home.hero.cta_secondary') }}
                                    </a>
                                @endif
                            @endauth
                        </div>

                        <div class="pt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('home.hero.trust_1') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('home.hero.trust_2') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('home.hero.trust_3') }}
                            </span>
                        </div>
                    </div>

                    <!-- Executive Operations Preview Card -->
                    <div class="lg:col-span-6">
                        <div class="bg-white rounded-lg border border-slate-200/90 shadow-sm shadow-xs overflow-hidden">
                            <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-bold tracking-tight">{{ __('home.preview.title') }}</h2>
                                    <p class="text-[11px] text-slate-400">{{ __('home.preview.subtitle') }}</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                    <span class="w-1.5 h-1.5 rounded-lg bg-emerald-400"></span>
                                    {{ __('home.preview.status') }}
                                </span>
                            </div>

                            <div class="p-5 space-y-5">
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/70">
                                        <span class="block text-[11px] font-medium text-slate-500">{{ __('home.preview.kpi_students') }}</span>
                                        <span class="mt-1 block text-lg sm:text-xl font-bold text-slate-900">{{ __('home.preview.kpi_students_val') }}</span>
                                    </div>
                                    <div class="p-3.5 rounded-lg bg-emerald-50/70 border border-emerald-200/70">
                                        <span class="block text-[11px] font-medium text-emerald-700">{{ __('home.preview.kpi_attendance') }}</span>
                                        <span class="mt-1 block text-lg sm:text-xl font-bold text-emerald-900">{{ __('home.preview.kpi_attendance_val') }}</span>
                                    </div>
                                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/70">
                                        <span class="block text-[11px] font-medium text-slate-500">{{ __('home.preview.kpi_collection') }}</span>
                                        <span class="mt-1 block text-sm sm:text-base font-bold text-slate-900 truncate">{{ __('home.preview.kpi_collection_val') }}</span>
                                    </div>
                                </div>

                                <div class="border border-slate-200/80 rounded-lg overflow-hidden">
                                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                                        <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider">{{ __('home.preview.recent_heading') }}</span>
                                    </div>
                                    <div class="divide-y divide-slate-100 text-xs sm:text-sm">
                                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ __('home.preview.receipt_1_name') }}</p>
                                                <p class="text-[11px] font-mono text-slate-400">{{ __('home.preview.receipt_1_id') }}</p>
                                            </div>
                                            <span class="font-bold text-emerald-700 whitespace-nowrap">{{ __('home.preview.receipt_1_amt') }}</span>
                                        </div>
                                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ __('home.preview.receipt_2_name') }}</p>
                                                <p class="text-[11px] font-mono text-slate-400">{{ __('home.preview.receipt_2_id') }}</p>
                                            </div>
                                            <span class="font-bold text-emerald-700 whitespace-nowrap">{{ __('home.preview.receipt_2_amt') }}</span>
                                        </div>
                                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ __('home.preview.receipt_3_name') }}</p>
                                                <p class="text-[11px] font-mono text-slate-400">{{ __('home.preview.receipt_3_id') }}</p>
                                            </div>
                                            <span class="font-bold text-emerald-700 whitespace-nowrap">{{ __('home.preview.receipt_3_amt') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Key Capabilities -->
        <section id="features" class="py-16 sm:py-20 bg-white border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-12">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('home.features.eyebrow') }}</p>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ __('home.features.heading') }}</h2>
                    <p class="mt-3 text-sm sm:text-base text-slate-600">{{ __('home.features.subheading') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach (__('home.features.items') as $index => $item)
                        <div class="p-6 rounded-lg border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:shadow-sm transition-all">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4 font-bold text-sm">
                                0{{ $index + 1 }}
                            </div>
                            <h3 class="text-base font-bold text-slate-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $item['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Integrated Modules -->
        <section id="modules" class="py-16 sm:py-20 bg-slate-50 border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-12">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('home.modules.eyebrow') }}</p>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ __('home.modules.heading') }}</h2>
                    <p class="mt-3 text-sm sm:text-base text-slate-600">{{ __('home.modules.subheading') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach (__('home.modules.items') as $module)
                        <div class="p-5 rounded-lg bg-white border border-slate-200/80 shadow-2xs hover:border-emerald-300 transition-colors">
                            <div class="flex items-center gap-2.5 mb-2.5">
                                <span class="w-2 h-2 rounded-lg bg-emerald-600 shrink-0"></span>
                                <h3 class="text-sm font-bold text-slate-900">{{ $module['name'] }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">{{ $module['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Role-Based Portals -->
        <section id="roles" class="py-16 sm:py-20 bg-white border-b border-slate-200/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-2xl mb-12">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ __('home.roles.eyebrow') }}</p>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ __('home.roles.heading') }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach (__('home.roles.items') as $role)
                        <div class="p-6 rounded-lg border border-slate-200 bg-white">
                            <span class="inline-block px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 text-xs font-bold mb-3">
                                {{ $role['role'] }}
                            </span>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $role['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Bottom CTA -->
        <section class="py-14 sm:py-16 bg-slate-900 text-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-5">
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ __('home.cta.heading') }}</h2>
                <p class="text-sm sm:text-base text-slate-300 max-w-xl mx-auto">{{ __('home.cta.subheading') }}</p>
                <div class="pt-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 transition-colors">
                            {{ __('home.hero.cta_dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 transition-colors">
                            {{ __('home.cta.button') }}
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">{{ __('app.name') }}</span>
                <span>|</span>
                <span>{{ __('app.tagline') }}</span>
            </div>

            <div class="flex items-center gap-5">
                <a href="{{ route('health') }}" class="hover:text-slate-800 transition-colors">{{ __('home.footer.status') }}</a>
                <span>&copy; {{ date('Y') }} {{ __('app.name') }}. {{ __('home.footer.rights') }}</span>
            </div>
        </div>
    </footer>
</body>
</html>
