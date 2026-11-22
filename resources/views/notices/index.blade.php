@php
    $canSend = auth()->user()->can('send-notices');
@endphp

<x-app-layout>
    <x-slot:title>
        বিজ্ঞপ্তি | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">বিজ্ঞপ্তি</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">অভিভাবক ও শিক্ষকদের জানান</p>
        </div>
    </x-slot:header>

    @if ($canSend)
        <x-card title="নতুন বিজ্ঞপ্তি" class="mb-6">
            <form method="POST" action="{{ route('notices.store') }}" class="space-y-4">
                @csrf

                <x-input name="title" label="শিরোনাম" :value="old('title')" required />
                <x-textarea name="body" label="বিস্তারিত" rows="4" required>{{ old('body') }}</x-textarea>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-select name="audience" label="কাদের জন্য" required :selected="old('audience', 'all')">
                        <option value="all">সবাই</option>
                        <option value="class">নির্দিষ্ট শ্রেণি</option>
                        <option value="parents">অভিভাবক</option>
                        <option value="teachers">শিক্ষক</option>
                    </x-select>

                    <x-select name="target_class_id" label="শ্রেণি (প্রযোজ্য হলে)" placeholder="নির্বাচন করুন" :selected="old('target_class_id')">
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name_bn ?? $class->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="channel" label="মাধ্যম" required :selected="old('channel', 'app')">
                        <option value="app">অ্যাপ</option>
                        <option value="sms">এসএমএস (সর্বোচ্চ ৪৮০ অক্ষর)</option>
                        <option value="both">উভয়</option>
                    </x-select>
                </div>

                <x-button type="submit" variant="primary">প্রকাশ করুন</x-button>
            </form>
        </x-card>
    @endif

    <x-card>
        @if ($notices->isEmpty())
            <x-empty-state title="কোনো বিজ্ঞপ্তি নেই" description="নতুন বিজ্ঞপ্তি প্রকাশ হলে এখানে দেখা যাবে।" />
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
