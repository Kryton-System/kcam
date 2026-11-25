<x-guest-layout>
    <x-slot:title>
        {{ __('auth.register.title') }} | {{ __('app.name') }}
    </x-slot:title>

    <div class="mb-6 text-center">
        <h3 class="text-xl font-bold text-slate-900">{{ __('auth.register.heading') }}</h3>
        <p class="text-xs text-slate-500 mt-1">{{ __('auth.register.subheading') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="space-y-3 pb-3 border-b border-slate-100">
            <h4 class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">{{ __('auth.register.school_section') }}</h4>

            <x-input
                name="name"
                :label="__('auth.register.school_name')"
                :placeholder="__('auth.register.school_name_placeholder')"
                :value="old('name')"
                required
            />

            <x-input
                name="name_bn"
                :label="__('auth.register.school_name_bn')"
                :placeholder="__('auth.register.school_name_bn_placeholder')"
                :value="old('name_bn')"
            />

            <x-input
                name="phone"
                :label="__('auth.register.phone')"
                placeholder="017xxxxxxxx"
                :value="old('phone')"
                required
            />

            <x-input
                name="address"
                :label="__('auth.register.address')"
                :placeholder="__('auth.register.address_placeholder')"
                :value="old('address')"
            />
        </div>

        <div class="space-y-3 pt-2">
            <h4 class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">{{ __('auth.register.owner_section') }}</h4>

            <x-input
                name="owner_name"
                :label="__('auth.register.owner_name')"
                :placeholder="__('auth.register.owner_name_placeholder')"
                :value="old('owner_name')"
                required
            />

            <x-input
                name="owner_email"
                :label="__('auth.register.owner_email')"
                type="email"
                placeholder="owner@school.com"
                :value="old('owner_email')"
                required
            />

            <x-input
                name="owner_password"
                :label="__('auth.register.owner_password')"
                type="password"
                placeholder="••••••••"
                required
            />

            <x-input
                name="owner_password_confirmation"
                :label="__('auth.register.owner_password_confirmation')"
                type="password"
                placeholder="••••••••"
                required
            />
        </div>

        <x-button type="submit" variant="primary" block="true" class="mt-4">
            {{ __('auth.register.submit') }}
        </x-button>
    </form>

    <div class="mt-6 text-center text-sm border-t border-slate-100 pt-4">
        <span class="text-slate-500 text-xs">{{ __('auth.register.have_account') }}</span>
        <a href="{{ route('login') }}" class="font-semibold text-emerald-700 hover:text-emerald-800 ml-1 text-xs">
            {{ __('auth.register.login_link') }}
        </a>
    </div>
</x-guest-layout>
