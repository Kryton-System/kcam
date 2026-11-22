<x-guest-layout>
    <x-slot:title>
        নতুন স্কুল নিবন্ধন | কেসিএএম
    </x-slot:title>

    <div class="mb-6 text-center">
        <h3 class="text-xl font-bold text-slate-900">আপনার স্কুল নিবন্ধন করুন</h3>
        <p class="text-xs text-slate-500 mt-1">মাত্র ৩ মিনিটে আপনার স্কুলের হিসাব ও তথ্য ডিজিটাল করুন</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="space-y-3 pb-3 border-b border-slate-100">
            <h4 class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">১. স্কুলের তথ্য</h4>

            <x-input
                name="name"
                label="স্কুলের নাম (ইংরেজি)"
                placeholder="যেমন: Green Valley Kindergarten"
                :value="old('name')"
                required
            />

            <x-input
                name="name_bn"
                label="স্কুলের নাম (বাংলায়)"
                placeholder="যেমন: গ্রিন ভ্যালি কিন্ডারগার্টেন"
                :value="old('name_bn')"
            />

            <x-input
                name="phone"
                label="স্কুলের অফিসিয়াল ফোন নম্বর"
                placeholder="017xxxxxxxx"
                :value="old('phone')"
                required
            />

            <x-input
                name="address"
                label="স্কুলের ঠিকানা"
                placeholder="রোড, এলাকা, উপজেলা/জেলা"
                :value="old('address')"
            />
        </div>

        <div class="space-y-3 pt-2">
            <h4 class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">২. প্রধান শিক্ষক / মালিকের তথ্য</h4>

            <x-input
                name="owner_name"
                label="আপনার পুরো নাম"
                placeholder="মালিক বা অধ্যক্ষের নাম"
                :value="old('owner_name')"
                required
            />

            <x-input
                name="owner_email"
                label="ইমেইল ঠিকানা"
                type="email"
                placeholder="owner@school.com"
                :value="old('owner_email')"
                required
            />

            <x-input
                name="owner_password"
                label="লগইন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)"
                type="password"
                placeholder="••••••••"
                required
            />

            <x-input
                name="owner_password_confirmation"
                label="পাসওয়ার্ড নিশ্চিত করুন"
                type="password"
                placeholder="••••••••"
                required
            />
        </div>

        <x-button type="submit" variant="primary" block="true" class="mt-4">
            স্কুল তৈরি করুন ও শুরু করুন
        </x-button>
    </form>

    <div class="mt-6 text-center text-sm border-t border-slate-100 pt-4">
        <span class="text-slate-500 text-xs">পূর্বে অ্যাকাউন্ট তৈরি করেছেন?</span>
        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800 ml-1 text-xs">
            লগইন করুন
        </a>
    </div>
</x-guest-layout>
