@props([
    'name',
    'label' => null,
    'type' => 'text',
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
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'block w-full rounded-lg border text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 sm:text-sm px-3.5 py-2.5 min-h-[48px] ' .
                    ($hasError
                        ? 'border-red-400 text-red-900 focus:border-red-500 focus:ring-red-500 bg-red-50/30'
                        : 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 bg-white')
            ]) }}
        />
    </div>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-slate-500" id="{{ $inputId }}-hint">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p class="mt-1.5 text-xs text-red-600 font-medium" id="{{ $inputId }}-error">{{ $errorMessage }}</p>
    @endif
</div>
