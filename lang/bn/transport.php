<?php

declare(strict_types=1);

return [
    'common' => [
        'route' => 'রুট',
        'vehicle' => 'গাড়ি',
        'seats' => 'আসন',
        'edit' => 'সম্পাদনা',
        'print' => 'প্রিন্ট',
        'save' => 'সংরক্ষণ',
        'cancel' => 'বাতিল',
        'student' => 'শিক্ষার্থী',
        'class' => 'শ্রেণি',
        'stop' => 'স্টপ',
        'stops' => 'স্টপেজ',
        'roster' => 'তালিকা',
        'driver' => 'চালক',
        'mobile' => 'মোবাইল',
        'no_vehicle' => 'গাড়ি নেই',
    ],

    'index' => [
        'title' => 'পরিবহন',
        'subtitle' => 'রুট ও গাড়ি',
        'new_route' => 'নতুন রুট',
        'new_vehicle' => 'নতুন গাড়ি',
        'routes' => 'রুট',
        'no_routes' => 'কোনো রুট নেই',
        'no_routes_hint' => 'রুট যোগ করলে এখানে দেখা যাবে',
        'vehicles' => 'গাড়ি',
        'no_vehicles' => 'কোনো গাড়ি নেই',
        'no_vehicles_hint' => 'গাড়ি যোগ করলে এখানে দেখা যাবে',
        'van_no' => 'গাড়ি নম্বর',
        'capacity' => 'ধারণক্ষমতা',
    ],

    'show' => [
        'monthly_total' => 'মাসিক মোট',
        'assign_student' => 'শিক্ষার্থী যোগ',
        'choose' => 'নির্বাচন করুন',
        'monthly_fee_label' => 'মাসিক ভাড়া (৳)',
        'assign' => 'যোগ করুন',
        'no_students' => 'কোনো শিক্ষার্থী নেই',
        'no_students_hint' => 'শিক্ষার্থী যোগ করলে এখানে দেখা যাবে',
        'fee' => 'মাসিক ভাড়া',
        'confirm_remove' => 'সরাতে চান?',
        'remove' => 'সরান',
    ],

    'print' => [
        'title' => 'রুট তালিকা',
        'van' => 'গাড়ি',
        'no' => 'ক্রম',
        'total' => 'মোট',
    ],

    'route_form' => [
        'title' => 'রুট',
        'edit_heading' => 'রুট সম্পাদনা',
        'new_heading' => 'নতুন রুট',
        'name_en' => 'রুট',
        'name_bn' => 'রুটের নাম (বাংলা)',
        'stops' => 'স্টপেজ (প্রতি লাইনে একটি)',
    ],

    'vehicle_form' => [
        'title' => 'গাড়ি',
        'edit_heading' => 'গাড়ি সম্পাদনা',
        'new_heading' => 'নতুন গাড়ি',
        'van_number' => 'গাড়ি নম্বর',
        'driver_name' => 'চালকের নাম',
        'driver_mobile' => 'চালকের মোবাইল',
        'capacity' => 'ধারণক্ষমতা (আসন)',
    ],

    'flash' => [
        'route_added' => 'রুট যোগ হয়েছে',
        'route_updated' => 'রুট হালনাগাদ হয়েছে',
        'student_assigned' => 'শিক্ষার্থী যোগ হয়েছে',
        'student_removed' => 'শিক্ষার্থী সরানো হয়েছে',
        'vehicle_added' => 'গাড়ি যোগ হয়েছে',
        'vehicle_updated' => 'গাড়ি হালনাগাদ হয়েছে',
    ],

    'errors' => [
        'no_vehicle' => 'এই রুটে কোনো গাড়ি নেই',
        'vehicle_full' => 'গাড়ির আসন পূর্ণ',
        'already_assigned' => 'শিক্ষার্থী আগে থেকেই একটি রুটে আছে',
        'vehicle_over_capacity' => 'এই গাড়িতে :riders জনের আসন নেই',
        'capacity_below_riders' => 'ইতিমধ্যে :riders জন শিক্ষার্থী আছে',
        'fee_too_high' => 'মাসিক ভাড়া ৪ কোটি টাকার বেশি হতে পারবে না',
        'choose_active_student' => 'সক্রিয় শিক্ষার্থী নির্বাচন করুন',
    ],
];
