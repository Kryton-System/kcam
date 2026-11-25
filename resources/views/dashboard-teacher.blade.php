<x-app-layout>
    <x-slot:title>
        {{ __('dashboard.title') }} | {{ $school?->name_bn ?? $school?->name ?? __('dashboard.fallback_school_name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">{{ __('dashboard.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $school?->name_bn ?? $school?->name }}
                @if ($academicYear)
                    · {{ __('dashboard.academic_year') }}: {{ \App\Support\Helpers\LocaleFormat::digits($academicYear->name) }}
                @endif
            </p>
        </div>
        <div class="text-sm text-slate-500">{{ \App\Support\Helpers\LocaleFormat::date(now()) }}</div>
    </x-slot:header>

    @php
        $notMarked = max(0, $activeStudents - $attendance['marked']);
    @endphp

    @include('partials.dashboard-quick-actions', ['actions' => [
        ['route' => 'attendance.index', 'label' => __('dashboard.teacher.take_attendance'), 'show' => config('kcam.modules.attendance') && auth()->user()->can('take-attendance')],
        ['route' => 'exams.index', 'label' => __('dashboard.teacher.enter_marks'), 'show' => config('kcam.modules.exams') && auth()->user()->can('enter-marks')],
        ['route' => 'students.index', 'label' => __('dashboard.teacher.student_list'), 'show' => config('kcam.modules.students')],
        ['route' => 'notices.index', 'label' => __('dashboard.quick.notices'), 'show' => config('kcam.modules.notices')],
    ]])

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-dashboard-figure :label="__('dashboard.teacher.active_students')" :value="\App\Support\Helpers\LocaleFormat::number($activeStudents)" />
        <x-dashboard-figure :label="__('dashboard.teacher.present_today')" :value="\App\Support\Helpers\LocaleFormat::number($attendance['present'])" :note="$attendance['marked'] > 0 ? __('dashboard.teacher.present_rate', ['rate' => \App\Support\Helpers\LocaleFormat::number($attendance['rate'], 1)]) : null" tone="success" />
        <x-dashboard-figure :label="__('dashboard.teacher.absent_today')" :value="\App\Support\Helpers\LocaleFormat::number($attendance['absent'])" tone="warning" />
        <x-dashboard-figure :label="__('dashboard.teacher.pending_attendance')" :value="\App\Support\Helpers\LocaleFormat::number($notMarked)" :note="$notMarked > 0 ? __('dashboard.teacher.pending_note') : __('dashboard.teacher.all_marked')" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-card :title="__('dashboard.teacher.assigned_classes')">
                @if (empty($classRows))
                    <x-empty-state :title="__('dashboard.teacher.no_classes')" />
                @else
                    <x-table :headers="[__('dashboard.teacher.class'), __('dashboard.teacher.students'), __('dashboard.teacher.present'), __('dashboard.teacher.absent'), __('dashboard.teacher.status')]">
                        @foreach ($classRows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ \App\Support\Helpers\LocaleFormat::number($row['students']) }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ \App\Support\Helpers\LocaleFormat::number($row['present']) }}</td>
                                <td class="px-4 py-3 tabular-nums">{{ \App\Support\Helpers\LocaleFormat::number($row['absent']) }}</td>
                                <td class="px-4 py-3">
                                    @if ($row['students'] > 0 && $row['marked'] >= $row['students'])
                                        <x-badge variant="success">{{ __('dashboard.teacher.status_done') }}</x-badge>
                                    @else
                                        <x-badge variant="warning">{{ __('dashboard.teacher.status_pending') }}</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card :title="__('dashboard.teacher.upcoming_exams')">
                @if ($upcomingExams->isEmpty())
                    <x-empty-state :title="__('dashboard.teacher.no_exams')" />
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

        <div class="lg:col-span-3">
            <x-card :title="__('dashboard.teacher.recent_students')">
                @if ($recentStudents->isEmpty())
                    <x-empty-state :title="__('dashboard.teacher.no_students')" />
                @else
                    <x-table :headers="[__('dashboard.teacher.student'), __('dashboard.teacher.class'), __('dashboard.teacher.roll')]">
                        @foreach ($recentStudents as $student)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $student->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $student->currentEnrollment?->schoolClass?->name_bn ?? $student->currentEnrollment?->schoolClass?->name ?? __('dashboard.na') }}</td>
                                <td class="px-4 py-3 tabular-nums text-slate-600">{{ \App\Support\Helpers\LocaleFormat::digits($student->currentEnrollment?->roll_no ?? __('dashboard.default_roll')) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
