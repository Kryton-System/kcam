<x-guest-layout>
    <x-slot:title>
        লগইন | কেসিএএম
    </x-slot:title>

    <div class="mb-6 text-center">
        <h3 class="text-xl font-bold text-slate-900">লগইন করুন</h3>
        <p class="text-xs text-slate-500 mt-1">আপনার স্কুলের হিসাব ও তথ্য দেখতে লগইন করুন</p>
    </div>

    @if (session('status'))
        <div class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 p-3 rounded-lg border border-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-input
            name="login"
            label="মোবাইল নম্বর অথবা ইমেইল"
            placeholder="017xxxxxxxx বা user@example.com"
            :value="old('login')"
            required
            autofocus
        />

        <x-input
            name="password"
            label="পাসওয়ার্ড"
            type="password"
            placeholder="••••••••"
            required
        />

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span class="text-xs text-slate-600">মনে রাখুন</span>
            </label>

            <span class="text-xs text-emerald-700 hover:text-emerald-800 font-medium">
                পাসওয়ার্ড ভুলে গেছেন?
            </span>
        </div>

        <x-button type="submit" variant="primary" block="true" class="mt-2">
            লগইন
        </x-button>
    </form>

    @if (app()->environment('local'))
        <div class="mt-6 pt-5 border-t border-slate-200">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider text-center mb-3">
                ডেমো লগইন (এক ক্লিকে)
            </p>
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    onclick="document.getElementsByName('login')[0].value='owner@kcam.test'; document.getElementsByName('password')[0].value='password';"
                    class="px-2.5 py-1.5 text-xs font-medium text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors"
                >
                    মালিক (Owner)
                </button>
                <button
                    type="button"
                    onclick="document.getElementsByName('login')[0].value='teacher@kcam.test'; document.getElementsByName('password')[0].value='password';"
                    class="px-2.5 py-1.5 text-xs font-medium text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition-colors"
                >
                    শিক্ষক (Teacher)
                </button>
                <button
                    type="button"
                    onclick="document.getElementsByName('login')[0].value='accountant@kcam.test'; document.getElementsByName('password')[0].value='password';"
                    class="px-2.5 py-1.5 text-xs font-medium text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg border border-amber-200 transition-colors"
                >
                    হিসাবরক্ষক (Accountant)
                </button>
                <button
                    type="button"
                    onclick="document.getElementsByName('login')[0].value='parent@kcam.test'; document.getElementsByName('password')[0].value='password';"
                    class="px-2.5 py-1.5 text-xs font-medium text-purple-800 bg-purple-50 hover:bg-purple-100 rounded-lg border border-purple-200 transition-colors"
                >
                    অভিভাবক (Parent)
                </button>
            </div>
        </div>
    @endif

    @if (config('kcam.mode') === 'multi')
    <div class="mt-6 text-center text-sm border-t border-slate-100 pt-4">
        <span class="text-slate-500 text-xs">আপনার স্কুল কি এখনও যুক্ত হয়নি?</span>
        <a href="{{ route('register') }}" class="font-semibold text-emerald-700 hover:text-emerald-800 ml-1 text-xs">
            নতুন স্কুল নিবন্ধন করুন
        </a>
    </div>
    @endif
</x-guest-layout>
