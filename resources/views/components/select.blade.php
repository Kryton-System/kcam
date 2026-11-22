@props([
    'name',
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'selected' => null,
    'error' => null,
    'hint' => null,
    'id' => null,
])

@php
    $selectId = $id ?? $name;
    $hasError = $error || ($errors->has($name) ?? false);
    $errorMessage = $error ?? ($errors->first($name) ?? null);
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $selectId }}" class="block text-sm font-medium text-slate-800 mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-red-500 font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-lg shadow-sm">
        <select
            id="{{ $selectId }}"
            name="{{ $name }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'block w-full rounded-lg border text-slate-900 focus:outline-none focus:ring-2 sm:text-sm px-3.5 py-2.5 min-h-[48px] bg-white ' .
                    ($hasError
                        ? 'border-red-400 text-red-900 focus:border-red-500 focus:ring-red-500'
                        : 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-500')
            ]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach ($options as $val => $text)
                <option value="{{ $val }}" @selected((string) $selected === (string) $val)>
                    {{ $text }}
                </option>
            @endforeach

            {{ $slot }}
        </select>
    </div>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $errorMessage }}</p>
    @endif
</div>
