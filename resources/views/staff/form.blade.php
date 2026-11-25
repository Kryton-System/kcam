@php
    use App\Support\Money;

    $isEdit = $staff->exists;
    $action = $isEdit ? route('staff.update', $staff) : route('staff.store');
    $heading = $isEdit ? __('staff.form.heading_edit') : __('staff.form.heading_create');
@endphp

<x-app-layout>
    <x-slot:title>
        {{ $heading }} | {{ __('app.name') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ $heading }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('staff.form.subheading') }}</p>
        </div>

        <div>
            <x-button :href="route('staff.index')" variant="secondary">← {{ __('staff.form.list') }}</x-button>
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
                    <x-input name="name" :label="__('staff.form.name_en')" :value="old('name', $staff->name)" required />
                    <x-input name="name_bn" :label="__('staff.form.name_bn')" :value="old('name_bn', $staff->name_bn)" />
                    <x-input name="designation" :label="__('staff.form.designation')" :value="old('designation', $staff->designation)" required />
                    <x-input name="phone" :label="__('staff.form.mobile')" type="tel" placeholder="01XXXXXXXXX" :value="old('phone', $staff->phone)" />
                    <x-input name="joined_on" :label="__('staff.form.joined_on')" type="date" :value="old('joined_on', $staff->joined_on?->format('Y-m-d'))" />
                    <x-input name="salary" :label="__('staff.form.salary')" inputmode="decimal" :value="old('salary', $isEdit ? Money::toTaka($staff->salary_basic) : '')" required />
                    <x-select name="status" :label="__('staff.form.status')" :selected="old('status', $staff->status)">
                        <option value="active">{{ __('staff.form.active') }}</option>
                        <option value="inactive">{{ __('staff.form.inactive') }}</option>
                    </x-select>
                </div>
            </x-card>

            <x-button type="submit">{{ __('staff.form.save') }}</x-button>
        </form>
    </div>
</x-app-layout>
