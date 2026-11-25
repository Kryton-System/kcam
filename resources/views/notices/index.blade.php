@php
    $canSend = auth()->user()->can('send-notices');
@endphp

<x-app-layout>
    <x-slot:title>
        {{ __('notices.index.title') }}
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('notices.index.heading') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('notices.index.subheading') }}</p>
        </div>
    </x-slot:header>

    @if ($canSend)
        <x-card :title="__('notices.index.new_notice')" class="mb-6">
            <form method="POST" action="{{ route('notices.store') }}" class="space-y-4">
                @csrf

                <x-input name="title" :label="__('notices.index.notice_title')" :value="old('title')" required />
                <x-textarea name="body" :label="__('notices.index.body')" rows="4" required>{{ old('body') }}</x-textarea>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-select name="audience" :label="__('notices.index.audience')" required :selected="old('audience', 'all')">
                        <option value="all">{{ __('notices.index.audience_all') }}</option>
                        <option value="class">{{ __('notices.index.audience_class') }}</option>
                        <option value="parents">{{ __('notices.index.audience_parents') }}</option>
                        <option value="teachers">{{ __('notices.index.audience_teachers') }}</option>
                    </x-select>

                    <x-select name="target_class_id" :label="__('notices.index.target_class')" :placeholder="__('notices.index.choose')" :selected="old('target_class_id')">
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name_bn ?? $class->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="channel" :label="__('notices.index.channel')" required :selected="old('channel', 'app')">
                        <option value="app">{{ __('notices.index.channel_app') }}</option>
                        <option value="sms">{{ __('notices.index.channel_sms') }}</option>
                        <option value="both">{{ __('notices.index.channel_both') }}</option>
                    </x-select>
                </div>

                <x-button type="submit" variant="primary">{{ __('notices.index.publish') }}</x-button>
            </form>
        </x-card>
    @endif

    <x-card>
        @if ($notices->isEmpty())
            <x-empty-state :title="__('notices.index.empty_title')" :description="__('notices.index.empty_description')" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($notices as $notice)
                    <article class="py-4">
                        <h3 class="font-semibold text-slate-900">{{ $notice->title_bn ?? $notice->title }}</h3>
                        <p class="text-sm text-slate-700 mt-1 whitespace-pre-line">{{ $notice->body_bn ?? $notice->body }}</p>
                        <p class="text-xs text-slate-400 mt-2">
                            {{ $notice->author?->name }} | {{ $notice->sent_at?->format('d/m/Y H:i') }}
                            @if ($notice->targetClass)
                                | {{ $notice->targetClass->name_bn ?? $notice->targetClass->name }}
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $notices->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
