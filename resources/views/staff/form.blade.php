@php
    use App\Support\Money;

    $isEdit = $staff->exists;
    $action = $isEdit ? route('staff.update', $staff) : route('staff.store');
    $heading = $isEdit ? 'কর্মী সম্পাদনা / Edit staff' : 'নতুন কর্মী / Add staff';
@endphp

<x-app-layout>
    <x-slot:title>
        {{ $heading }} | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ $heading }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">বেতন টাকায় লিখুন, যেমন 12000.50 / Enter salary in taka, e.g. 12000.50</p>
        </div>

        <div>
            <x-button :href="route('staff.index')" variant="secondary">← তালিকা / List</x-button>
        </div>
    </x-slot:header>

    <div class="max-w-3xl">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <x-card class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input name="name" label="নাম (ইংরেজি) / Name (English)" :value="old('name', $staff->name)" required />
                    <x-input name="name_bn" label="নাম (বাংলা) / Name (Bangla)" :value="old('name_bn', $staff->name_bn)" />
                    <x-input name="designation" label="পদবি / Designation" :value="old('designation', $staff->designation)" required />
                    <x-input name="phone" label="মোবাইল / Mobile" type="tel" placeholder="01XXXXXXXXX" :value="old('phone', $staff->phone)" />
                    <x-input name="joined_on" label="যোগদানের তারিখ / Joining date" type="date" :value="old('joined_on', $staff->joined_on?->format('Y-m-d'))" />
                    <x-input name="salary" label="মাসিক বেতন (টাকা) / Monthly salary (taka)" inputmode="decimal" :value="old('salary', $isEdit ? Money::toTaka($staff->salary_basic) : '')" required />
                    <x-select name="status" label="অবস্থা / Status" :selected="old('status', $staff->status)">
                        <option value="active">সক্রিয় / Active</option>
                        <option value="inactive">নিষ্ক্রিয় / Inactive</option>
                    </x-select>
                </div>
            </x-card>

            <x-button type="submit">সংরক্ষণ করুন / Save</x-button>
        </form>
    </div>
</x-app-layout>
