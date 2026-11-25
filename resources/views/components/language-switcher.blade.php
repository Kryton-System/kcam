@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-lg border border-slate-200 bg-white p-0.5 text-xs font-semibold '.$class]) }} role="group" aria-label="Language / ভাষা">
    @foreach (['bn' => 'বাংলা', 'en' => 'English'] as $code => $languageName)
        <a
            href="{{ route('locale.switch', $code) }}"
            hreflang="{{ $code }}"
            lang="{{ $code }}"
            @if (app()->isLocale($code)) aria-current="true" class="rounded-md px-2.5 py-1 bg-emerald-600 text-white" @else class="rounded-md px-2.5 py-1 text-slate-600 hover:text-slate-900" @endif
        >{{ $languageName }}</a>
    @endforeach
</div>
