<?php

declare(strict_types=1);

return [
    'roles' => [
        'owner' => 'মালিক',
        'teacher' => 'শিক্ষক',
        'accountant' => 'হিসাবরক্ষক',
        'parent' => 'অভিভাবক',
        'super_admin' => 'সুপার অ্যাডমিন',
    ],
    'student_status' => [
        'active' => 'সক্রিয়',
        'inactive' => 'নিষ্ক্রিয়',
        'transferred' => 'বদলি',
        'graduated' => 'পাশ',
        'dropped' => 'ঝরে পড়া',
    ],
    'payment_status' => [
        'pending' => 'অপেক্ষমান',
        'completed' => 'সম্পন্ন',
        'failed' => 'ব্যর্থ',
        'refunded' => 'ফেরত',
        'cancelled' => 'বাতিল',
    ],
    'fee_head_type' => [
        'monthly' => 'মাসিক',
        'one_time' => 'এককালীন',
        'yearly' => 'বার্ষিক',
    ],
    'attendance_status' => [
        'present' => 'উপস্থিত',
        'absent' => 'অনুপস্থিত',
        'late' => 'বিলম্বিত',
        'excused' => 'ছুটি',
    ],
    'payment_method' => [
        'cash' => 'নগদ',
        'bkash' => 'বিকাশ',
        'nagad' => 'নগদ (মোবাইল)',
        'rocket' => 'রকেট',
        'card' => 'কার্ড',
        'bank_transfer' => 'ব্যাংক ট্রান্সফার',
    ],
];
