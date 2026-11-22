<x-app-layout>
    <x-slot:title>
        ড্যাশবোর্ড | {{ $school?->name_bn ?? $school?->name ?? 'কেসিএএম' }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                স্বাগতম, {{ auth()->user()->name }}
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                {{ $school?->name_bn ?? $school?->name }} | শিক্ষাবর্ষ: {{ $academicYear?->name ?? '২০২৬' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                ● অনলাইন
            </span>
            <span class="text-xs text-slate-500 font-medium">
                {{ \App\Support\Helpers\BanglaHelper::formatDate(now()) }}
            </span>
        </div>
    </x-slot:header>

    <!-- Top Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5 mb-6">
        <!-- 1. Total Students -->
        <x-stat-card
            title="মোট শিক্ষার্থী"
            :value="$totalStudentsBn"
            subtitle="সক্রিয় শিক্ষার্থী"
            color="emerald"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 2. Present Today -->
        <x-stat-card
            title="আজকের উপস্থিতি"
            :value="$presentTodayBn"
            :subtitle="'হার: ' . $attendanceRateBn . '%'"
            color="blue"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 3. Current Month Collected -->
        <x-stat-card
            title="চলতি মাসের আদায়"
            :value="$totalCollectedFormatted"
            subtitle="পরিশোধিত"
            color="purple"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <!-- 4. Total Due -->
        <x-stat-card
            title="বকেয়া ফি"
            :value="$totalDueFormatted"
            subtitle="চলতি মাস"
            color="amber"
        >
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Quick Actions Panel (Big Touch Targets) -->
    <div class="mb-8">
        <h3 class="text-sm font-bold text-slate-800 mb-3 uppercase tracking-wider">দ্রুত কাজ</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('attendance.index') }}" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-xs transition-all touch-target text-center group">
                <div class="w-12 h-12 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center mb-2 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-800">হাজিরা নিন</span>
                <span class="text-[11px] text-slate-500 mt-0.5">দৈনিক উপস্থিতি</span>
            </a>

            <a href="{{ route('fees.index') }}" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-xs transition-all touch-target text-center group">
                <div class="w-12 h-12 rounded-xl bg-purple-100/70 text-purple-700 flex items-center justify-center mb-2 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-800">ফি আদায়</span>
                <span class="text-[11px] text-slate-500 mt-0.5">রসিদ প্রদান</span>
            </a>

            <a href="{{ route('students.create') }}" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-xs transition-all touch-target text-center group">
                <div class="w-12 h-12 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center mb-2 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-800">নতুন ভর্তি</span>
                <span class="text-[11px] text-slate-500 mt-0.5">শিক্ষার্থী যোগ</span>
            </a>

            <a href="{{ route('notices.index') }}" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-slate-200/80 hover:border-emerald-500 hover:shadow-xs transition-all touch-target text-center group">
                <div class="w-12 h-12 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center mb-2 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-800">বিজ্ঞপ্তি ও এসএমএস</span>
                <span class="text-[11px] text-slate-500 mt-0.5">অভিভাবকদের বার্তা</span>
            </a>
        </div>
    </div>

    <!-- Bottom Grids: Recent Transactions & Notices -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Payments (2 cols) -->
        <div class="lg:col-span-2">
            <x-card title="সাম্প্রতিক ফি আদায়">
                @if ($recentPayments->isEmpty())
                    <x-empty-state
                        title="এখনও কোনো ফি আদায় হয়নি"
                        description="শিক্ষার্থীর ফি আদায় করতে উপরের 'ফি আদায়' বাটনে ক্লিক করুন।"
                    />
                @else
                    <x-table :headers="['শিক্ষার্থী', 'রোল', 'টাকা', 'মাধ্যম', 'সময়']">
                        @foreach ($recentPayments as $payment)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $payment->invoice?->student?->name_bn ?? $payment->invoice?->student?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ \App\Support\Helpers\BanglaHelper::toBanglaDigits($payment->invoice?->student?->currentEnrollment?->roll_no ?? '1') }}
                                </td>
                                <td class="px-4 py-3 font-bold text-emerald-700">
                                    {{ \App\Support\Helpers\BanglaHelper::formatTaka($payment->amount, true) }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <x-badge variant="neutral">{{ $payment->method?->labelBn() ?? 'নগদ' }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $payment->paid_at ? \App\Support\Helpers\BanglaHelper::formatDate($payment->paid_at) : 'আজ' }}
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>
        </div>

        <!-- Recent Notices (1 col) -->
        <div class="lg:col-span-1">
            <x-card title="সর্বশেষ বিজ্ঞপ্তি">
                @if ($recentNotices->isEmpty())
                    <x-empty-state
                        title="কোনো বিজ্ঞপ্তি নেই"
                        description="অভিভাবকদের জন্য নতুন নোটিশ তৈরি করুন।"
                    />
                @else
                    <div class="space-y-3">
                        @foreach ($recentNotices as $notice)
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                                <h4 class="text-sm font-bold text-slate-900">
                                    {{ $notice->title_bn ?? $notice->title }}
                                </h4>
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2">
                                    {{ $notice->body_bn ?? $notice->body }}
                                </p>
                                <div class="mt-2 text-[11px] text-slate-400">
                                    {{ $notice->sent_at ? \App\Support\Helpers\BanglaHelper::formatDate($notice->sent_at) : 'নতুন' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
