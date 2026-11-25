<?php

declare(strict_types=1);

return [
    'errors' => [
        'invalid_mobile' => 'সঠিক মোবাইল নম্বর দিন (যেমন 017XXXXXXXX)।',
    ],

    'flash' => [
        'admitted' => 'শিক্ষার্থী সফলভাবে ভর্তি করা হয়েছে।',
        'updated' => 'তথ্য হালনাগাদ হয়েছে।',
    ],

    'religions' => [
        'islam' => 'ইসলাম',
        'hinduism' => 'হিন্দু',
        'buddhism' => 'বৌদ্ধ',
        'christianity' => 'খ্রিস্টান',
        'other' => 'অন্যান্য',
    ],

    'genders' => [
        'male' => 'ছাত্র (ছেলে)',
        'female' => 'ছাত্রী (মেয়ে)',
        'other' => 'অন্যান্য',
    ],

    'relations' => [
        'father' => 'পিতা',
        'mother' => 'মাতা',
        'other' => 'অন্যান্য অভিভাবক',
    ],

    'create' => [
        'section_basic' => '১. মৌলিক তথ্য',
        'section_academic' => '২. শিক্ষাগত তথ্য',
        'section_contact' => '৪. যোগাযোগের তথ্য',
        'section_address' => '৫. ঠিকানার তথ্য',
        'section_documents' => '৬. নথি ও ছবি',
        'required_hint' => 'প্রয়োজনীয় ঘর',
        'guardian_mobile' => 'এসএমএস ও ফি-র বার্তার জন্য মোবাইল নম্বর',
        'title' => 'নতুন শিক্ষার্থী ভর্তি',
        'subtitle' => 'শিক্ষার্থীর তথ্য এবং অভিভাবকের যোগাযোগের বিবরণ লিখুন',
        'back' => '← তালিকায় ফিরে যান',
        'submit' => 'ভর্তি সম্পন্ন করুন',
        'cancel' => 'বাতিল',
        'section_student' => '১. শিক্ষার্থীর তথ্য',
        'section_class' => '২. শ্রেণি ও শাখা বরাদ্দ',
        'section_guardian' => '৩. অভিভাবকের তথ্য',
        'select' => 'নির্বাচন করুন',
        'select_class' => 'শ্রেণি নির্বাচন করুন',
        'optional_section' => 'শাখা (ঐচ্ছিক)',
    ],

    'fields' => [
        'name' => 'শিক্ষার্থীর পুরো নাম (ইংরেজি)',
        'name_bn' => 'শিক্ষার্থীর পুরো নাম (বাংলায়)',
        'dob' => 'জন্ম তারিখ',
        'gender' => 'লিঙ্গ',
        'blood_group' => 'রক্তের গ্রুপ',
        'religion' => 'ধর্ম',
        'address' => 'বর্তমান ঠিকানা',
        'birth_reg_no' => 'জন্ম নিবন্ধন নম্বর (BDRIS)',
        'photo' => 'শিক্ষার্থীর ছবি',
        'class' => 'শ্রেণি',
        'section' => 'শাখা',
        'roll_no' => 'রোল নং',
        'guardian_name' => 'অভিভাবকের নাম',
        'guardian_relation' => 'সম্পর্ক',
        'guardian_mobile' => 'মোবাইল নম্বর (এসএমএস যাবে)',
        'guardian_nid' => 'অভিভাবকের এনআইডি নম্বর',
        'guardian_nid_front' => 'অভিভাবকের এনআইডি কপি (সামনে)',
        'guardian_nid_back' => 'অভিভাবকের এনআইডি কপি (পিছনে)',
    ],

    'hints' => [
        'birth_reg_no' => '১৭ সংখ্যার জন্ম নিবন্ধন নম্বর। এখনো নিবন্ধন না থাকলে ফাঁকা রাখুন।',
        'photo' => 'JPG, PNG বা WebP, সর্বোচ্চ ২ MB।',
        'guardian_nid' => '১০, ১৩ বা ১৭ সংখ্যা।',
        'guardian_nid_scan' => 'JPG, PNG বা PDF, প্রতিটি সর্বোচ্চ ২ MB।',
    ],

    'placeholders' => [
        'name' => 'যেমন: Sabbir Hossain',
        'name_bn' => 'যেমন: সাব্বির হোসেন',
        'guardian_name' => 'পিতা/মাতার নাম',
        'mobile' => '017XXXXXXXX',
    ],
    'show' => [
        'list' => 'তালিকা',
        'edit' => 'সম্পাদনা',
        'section_student' => 'শিক্ষার্থী',
        'section_guardian' => 'অভিভাবক',
        'no_photo' => 'ছবি নেই',
        'nid_copies' => 'অভিভাবকের এনআইডি কপি',
        'view_front' => 'সামনের অংশ দেখুন',
        'view_back' => 'পিছনের অংশ দেখুন',
    ],

    'index' => [
        'title' => 'শিক্ষার্থী তালিকা',
        'subtitle' => 'সকল ভর্তিকৃত শিক্ষার্থীর তথ্য ও তালিকা',
        'new_admission' => 'নতুন ভর্তি',
        'search_placeholder' => 'নাম, শিক্ষার্থী আইডি বা জন্ম নিবন্ধন নম্বর দিয়ে খুঁজুন...',
        'all_statuses' => 'সব অবস্থা',
        'status_active' => 'সক্রিয়',
        'status_inactive' => 'নিষ্ক্রিয়',
        'sort_name' => 'সাজান: নাম',
        'sort_code' => 'সাজান: শিক্ষার্থী আইডি',
        'sort_newest' => 'সাজান: সর্বশেষ ভর্তি আগে',
        'all_classes' => 'সব শ্রেণি',
        'filter' => 'ফিল্টার করুন',
        'reset' => 'রিসেট',
        'empty_title' => 'কোনো শিক্ষার্থী পাওয়া যায়নি',
        'empty_description' => "নতুন শিক্ষার্থী যোগ করতে উপরের 'নতুন ভর্তি' বাটনে ক্লিক করুন।",
        'admit_student' => 'শিক্ষার্থী ভর্তি করুন',
        'columns' => [
            'roll' => 'রোল',
            'id' => 'আইডি',
            'name' => 'নাম',
            'class_section' => 'শ্রেণি ও শাখা',
            'guardian_mobile' => 'অভিভাবকের মোবাইল',
            'status' => 'অবস্থা',
        ],
    ],

    'edit' => [
        'keep_current' => 'নতুন ফাইল না দিলে বর্তমানটি থাকবে।',
        'title' => 'শিক্ষার্থীর তথ্য সম্পাদনা',
        'page_title' => 'সম্পাদনা',
        'back' => '← ফিরে যান',
        'cancel' => 'বাতিল',
        'save' => 'সংরক্ষণ',
    ],
];
