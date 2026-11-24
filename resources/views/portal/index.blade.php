<x-app-layout>
    <x-slot:title>অভিভাবক পোর্টাল / Parent Portal</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900">আমার সন্তান / My Children</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">অভিভাবক পোর্টাল / Parent Portal</p>
        </div>
    </x-slot:header>

    @if ($children->isEmpty())
        <x-card>
            <p class="text-sm text-slate-600">কোনো শিশু পাওয়া যায়নি। স্কুলে নিবন্ধিত মোবাইল নম্বর দিয়ে লগইন করুন। / No children found. Log in with the mobile number registered at school.</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($children as $child)
                <a href="{{ route('portal.children.show', $child) }}" class="block">
                    <x-card class="hover:border-emerald-400 transition">
                        <div class="p-5">
                            <h2 class="font-bold text-slate-900">{{ $child->name_bn ?? $child->name }}</h2>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $child->name }} | {{ $child->student_code }}</p>
                            <p class="text-sm text-slate-700 mt-3">
                                শ্রেণি / Class: {{ $child->currentEnrollment?->schoolClass?->name_bn ?? $child->currentEnrollment?->schoolClass?->name ?? '-' }}
                                | রোল / Roll: {{ $child->currentEnrollment?->roll_no ?? '-' }}
                            </p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
