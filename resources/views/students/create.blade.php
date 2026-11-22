<x-app-layout>
    <x-slot:title>
        নতুন শিক্ষার্থী ভর্তি | কেসিএএম
    </x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">
                নতুন শিক্ষার্থী ভর্তি
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                শিক্ষার্থীর তথ্য এবং অভিভাবকের যোগাযোগের বিবরণ লিখুন
            </p>
        </div>

        <div>
            <x-button :href="route('students.index')" variant="secondary">
                ← তালিকায় ফিরে যান
            </x-button>
        </div>
    </x-slot:header>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('students.store') }}">
            @csrf

            <!-- 1. Student Information -->
            <x-card title="১. শিক্ষার্থীর তথ্য" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="name"
                        label="শিক্ষার্থীর পুরো নাম (ইংরেজি)"
                        placeholder="যেমন: Sabbir Hossain"
                        :value="old('name')"
                        required
                    />

                    <x-input
                        name="name_bn"
                        label="শিক্ষার্থীর পুরো নাম (বাংলায়)"
                        placeholder="যেমন: সাব্বির হোসেন"
                        :value="old('name_bn')"
                    />

                    <x-input
                        name="dob"
                        label="জন্ম তারিখ"
                        type="date"
                        :value="old('dob')"
                    />

                    <x-select name="gender" label="লিঙ্গ" required :selected="old('gender', 'male')">
                        <option value="male">ছাত্র (ছেলে)</option>
                        <option value="female">ছাত্রী (মেয়ে)</option>
                        <option value="other">অন্যান্য</option>
                    </x-select>

                    <x-select name="blood_group" label="রক্তের গ্রুপ" :selected="old('blood_group')">
                        <option value="">নির্বাচন করুন</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </x-select>
                </div>
            </x-card>

            <!-- 2. Class & Enrollment -->
            <x-card title="২. শ্রেণি ও শাখা বরাদ্দ" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-select name="class_id" label="শ্রেণি" required :selected="old('class_id')">
                        <option value="">শ্রেণি নির্বাচন করুন</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name_bn ?? $c->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select name="section_id" label="শাখা (ঐচ্ছিক)" :selected="old('section_id')">
                        <option value="">শাখা নির্বাচন করুন</option>
                        @foreach ($classes as $c)
                            @foreach ($c->sections as $sec)
                                <option value="{{ $sec->id }}">{{ $c->name_bn ?? $c->name }} - {{ $sec->name_bn ?? $sec->name }}</option>
                            @endforeach
                        @endforeach
                    </x-select>

                    <x-input
                        name="roll_no"
                        label="রোল নং"
                        type="number"
                        placeholder="১"
                        :value="old('roll_no')"
                    />
                </div>
            </x-card>

            <!-- 3. Guardian Information -->
            <x-card title="৩. অভিভাবকের তথ্য" class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-input
                        name="guardian_name"
                        label="অভিভাবকের নাম"
                        placeholder="যেমন: পিতা/মাতার নাম"
                        :value="old('guardian_name')"
                        required
                    />

                    <x-select name="guardian_relation" label="সম্পর্ক" required :selected="old('guardian_relation', 'father')">
                        <option value="father">পিতা</option>
                        <option value="mother">মাতা</option>
                        <option value="other">অন্যান্য অভিভাবক</option>
                    </x-select>

                    <x-input
                        name="guardian_mobile"
                        label="মোবাইল নম্বর (এসএমএস যাবে)"
                        placeholder="017xxxxxxxx"
                        :value="old('guardian_mobile')"
                        required
                    />
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button :href="route('students.index')" variant="secondary">
                    বাতিল
                </x-button>

                <x-button type="submit" variant="primary" size="lg">
                    ভর্তি সম্পন্ন করুন
                </x-button>
            </div>
        </form>
    </div>
</x-app-layout>
