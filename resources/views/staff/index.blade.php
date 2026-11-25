@php
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('staff.index.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('staff.index.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('staff.index.subheading') }}</p>
        </div>

        <div class="flex gap-2">
            <x-button :href="route('payroll.index')" variant="secondary">{{ __('staff.index.payroll') }}</x-button>
            <x-button :href="route('staff.create')">{{ __('staff.index.add') }}</x-button>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-input name="search" :placeholder="__('staff.index.search_placeholder')" :value="$search" />
            <x-button type="submit" variant="secondary">{{ __('staff.index.search') }}</x-button>
        </form>
    </x-card>

    <x-card class="mb-6">
        @if ($staff->isEmpty())
            <x-empty-state :title="__('staff.index.empty_title')" :description="__('staff.index.empty_description')" />
        @else
            <x-table :headers="[__('staff.index.col_name'), __('staff.index.col_designation'), __('staff.index.col_mobile'), __('staff.index.col_joined'), __('staff.index.col_salary'), __('staff.index.col_status'), '']">
                @foreach ($staff as $member)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">
                            <div>{{ $member->name_bn ?: $member->name }}</div>
                            @if ($member->name_bn)
                                <div class="text-[11px] text-slate-400">{{ $member->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $member->designation }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $member->phone ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $member->joined_on ? \App\Support\Helpers\LocaleFormat::date($member->joined_on) : '-' }}</td>
                        <td class="px-4 py-3">{{ \App\Support\Helpers\LocaleFormat::taka($member->salary_basic, true) }}</td>
                        <td class="px-4 py-3">{{ $member->status === 'active' ? __('staff.index.active') : __('staff.index.inactive') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('staff.edit', $member) }}" class="text-emerald-700 font-semibold">{{ __('staff.index.edit') }}</a>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4">{{ $staff->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
