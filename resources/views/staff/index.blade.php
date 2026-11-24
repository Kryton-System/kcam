@php
    use App\Support\Helpers\BanglaHelper;
@endphp

<x-app-layout>
    <x-slot:title>
        কর্মী / Staff | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">কর্মী / Staff</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">শিক্ষক ও কর্মীদের তালিকা / Teachers and staff list</p>
        </div>

        <div class="flex gap-2">
            <x-button :href="route('payroll.index')" variant="secondary">বেতন / Payroll</x-button>
            <x-button :href="route('staff.create')">নতুন কর্মী / Add staff</x-button>
        </div>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-input name="search" placeholder="নাম, পদবি বা মোবাইল / Name, designation or mobile" :value="$search" />
            <x-button type="submit" variant="secondary">খুঁজুন / Search</x-button>
        </form>
    </x-card>

    <x-card class="mb-6">
        @if ($staff->isEmpty())
            <x-empty-state title="কোনো কর্মী নেই / No staff found" description="নতুন কর্মী যোগ করুন। / Add a staff member." />
        @else
            <x-table :headers="['নাম / Name', 'পদবি / Designation', 'মোবাইল / Mobile', 'যোগদান / Joined', 'মাসিক বেতন / Monthly salary', 'অবস্থা / Status', '']">
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
                        <td class="px-4 py-3 text-slate-700">{{ $member->joined_on ? BanglaHelper::formatDate($member->joined_on) : '-' }}</td>
                        <td class="px-4 py-3">{{ BanglaHelper::formatTaka($member->salary_basic, true) }}</td>
                        <td class="px-4 py-3">{{ $member->status === 'active' ? 'সক্রিয় / Active' : 'নিষ্ক্রিয় / Inactive' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('staff.edit', $member) }}" class="text-emerald-700 font-semibold">সম্পাদনা / Edit</a>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4">{{ $staff->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
