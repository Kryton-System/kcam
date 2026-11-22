@props([
    'name',
    'label' => null,
    'rows' => 3,
    'placeholder' => '',
    'required' => false,
    'error' => null,
    'hint' => null,
    'id' => null,
])

@php
    $inputId = $id ?? $name;
    $hasError = $error || ($errors->has($name) ?? false);
    $errorMessage = $error ?? ($errors->first($name) ?? null);
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-800 mb-1.5">
            {{ $label }}
            @if ($required)
                <span class="text-red-500 font-bold">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-lg shadow-sm">
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'block w-full rounded-lg border text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 sm:text-sm p-3 ' .
                    ($hasError
                        ? 'border-red-400 text-red-900 focus:border-red-500 focus:ring-red-500 bg-red-50/30'
                        : 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 bg-white')
            ]) }}
        >{{ $slot }}</textarea>
    </div>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p class="mt-1.5 text-xs text-red-600 font-medium">{{ $errorMessage }}</p>
    @endif
</div>
