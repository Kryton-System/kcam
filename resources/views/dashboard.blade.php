<x-app-layout>
    <x-slot:title>
        {{ __('dashboard.title') }} | {{ $school?->name_bn ?? $school?->name ?? __('dashboard.fallback_school_name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">
                {{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $school?->name_bn ?? $school?->name }}
                @if ($academicYear)
                    · {{ __('dashboard.academic_year') }}: {{ \App\Support\Helpers\LocaleFormat::digits($academicYear->name) }}
                @endif
            </p>
        </div>
        <div class="text-sm text-slate-500">
            {{ \App\Support\Helpers\LocaleFormat::date(now()) }}
        </div>
    </x-slot:header>

    @include('partials.dashboard-quick-actions', ['actions' => [
        ['route' => 'attendance.index', 'label' => __('dashboard.quick.attendance'), 'show' => config('kcam.modules.attendance') && auth()->user()->can('take-attendance')],
        ['route' => 'fees.index', 'label' => __('dashboard.quick.fees'), 'show' => config('kcam.modules.fees') && auth()->user()->can('collect-fees')],
        ['route' => 'students.create', 'label' => __('dashboard.quick.admission'), 'show' => auth()->user()->can('create-students')],
        ['route' => 'notices.index', 'label' => __('dashboard.quick.notices'), 'show' => config('kcam.modules.notices')],
    ]])

    {{-- Figures: compact blocks, one line of meaning each --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-dashboard-figure :label="__('dashboard.owner.total')" :value="\App\Support\Helpers\LocaleFormat::number($totalStudents)" :note="__('dashboard.owner.active_inactive', ['active' => \App\Support\Helpers\LocaleFormat::number($activeStudents), 'inactive' => \App\Support\Helpers\LocaleFormat::number($totalStudents - $activeStudents)])" />
        <x-dashboard-figure :label="__('dashboard.owner.attendance_today')" :value="\App\Support\Helpers\LocaleFormat::number($attendance['present']).' / '.\App\Support\Helpers\LocaleFormat::number($attendance['marked'])" :note="$attendance['marked'] > 0 ? __('dashboard.owner.present_rate', ['rate' => \App\Support\Helpers\LocaleFormat::number($attendance['rate'], 1)]) : __('dashboard.owner.not_marked')" />
        <x-dashboard-figure :label="__('dashboard.owner.collected_month')" :value="\App\Support\Helpers\LocaleFormat::taka($collectedThisMonth)" :note="__('dashboard.owner.collected_note')" tone="success" />
        <x-dashboard-figure :label="__('dashboard.owner.dues')" :value="\App\Support\Helpers\LocaleFormat::taka($outstandingDues)" :note="__('dashboard.owner.dues_note')" tone="warning" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-card :title="__('dashboard.owner.recent_transactions')">
                @if ($recentPayments->isEmpty())
                    <x-empty-state :title="__('dashboard.payments.empty_title')" :description="__('dashboard.payments.empty_description')" />
                @else
                    <x-table :headers="[__('dashboard.payments.student'), __('dashboard.accountant.receipt_no'), __('dashboard.payments.amount'), __('dashboard.payments.method'), __('dashboard.payments.time')]">
                        @foreach ($recentPayments as $payment)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->invoice?->student?->name ?? __('dashboard.na') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-700">{{ $payment->receipt?->serial_no ?? __('dashboard.na') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::taka($payment->amount) }}</td>
                                <td class="px-4 py-3"><x-badge variant="neutral">{{ $payment->method?->label() }}</x-badge></td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ \App\Support\Helpers\LocaleFormat::date($payment->paid_at) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <x-card :title="__('dashboard.owner.expenses_month')">
                    <p class="text-2xl font-semibold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::taka($expensesThisMonth) }}</p>
                    @if (config('kcam.modules.accounts'))
                        <a href="{{ route('accounts.index') }}" class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">{{ __('dashboard.owner.view_accounts') }}</a>
                    @endif
                </x-card>
                <x-card :title="__('dashboard.owner.staff')">
                    <p class="text-2xl font-semibold text-slate-900">{{ \App\Support\Helpers\LocaleFormat::number($staffActive) }} <span class="text-sm font-normal text-slate-500">/ {{ \App\Support\Helpers\LocaleFormat::number($staffTotal) }}</span></p>
                    <p class="text-sm text-slate-500 mt-1">{{ __('dashboard.owner.staff_note') }}</p>
                </x-card>
            </div>
        </div>

        <div class="space-y-6">
            <x-card :title="__('dashboard.owner.upcoming_exams')">
                @if ($upcomingExams->isEmpty())
                    <x-empty-state :title="__('dashboard.owner.no_exams')" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($upcomingExams as $exam)
                            <li class="py-2.5 flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-slate-900">{{ $exam->name_bn ?? $exam->name }}</span>
                                <span class="text-xs text-slate-500 whitespace-nowrap">{{ \App\Support\Helpers\LocaleFormat::date($exam->starts_on) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card :title="__('dashboard.notices.title')">
                @if ($recentNotices->isEmpty())
                    <x-empty-state :title="__('dashboard.notices.empty_title')" :description="__('dashboard.notices.empty_description')" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recentNotices as $notice)
                            <li class="py-2.5">
                                <p class="text-sm font-medium text-slate-900">{{ $notice->title_bn ?? $notice->title }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $notice->sent_at ? \App\Support\Helpers\LocaleFormat::date($notice->sent_at) : __('dashboard.new') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
