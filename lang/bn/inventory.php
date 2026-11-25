<?php

declare(strict_types=1);

return [
    'title' => 'ইনভেন্টরি',
    'index' => [
        'subtitle' => 'স্কুলের মালামালের স্টক',
        'new_item' => 'নতুন আইটেম',
        'name' => 'নাম',
        'unit' => 'একক',
        'unit_placeholder' => 'pcs, kg, box',
        'low_stock_level' => 'সতর্কতার সীমা',
        'add' => 'যোগ করুন',
        'empty_title' => 'কোনো আইটেম নেই',
        'empty_description' => 'প্রথম আইটেম যোগ করুন',
        'col_stock' => 'স্টক',
        'col_status' => 'অবস্থা',
        'low_stock' => 'কম স্টক',
        'ok' => 'পর্যাপ্ত',
        'ledger' => 'খাতা',
    ],
    'show' => [
        'current_stock' => 'বর্তমান স্টক',
        'new_movement' => 'নতুন লেনদেন',
        'type' => 'ধরন',
        'quantity' => 'পরিমাণ',
        'quantity_hint' => 'সমন্বয়ে ঋণাত্মক সংখ্যা চলবে, যেমন -2',
        'reason' => 'কারণ',
        'note' => 'মন্তব্য',
        'save' => 'সংরক্ষণ',
        'ledger_title' => 'স্টক খাতা',
        'empty_title' => 'কোনো লেনদেন নেই',
        'empty_description' => 'প্রথম লেনদেন যোগ করুন',
        'col_date' => 'তারিখ',
        'col_type' => 'ধরন',
        'col_qty' => 'পরিমাণ',
        'col_reason' => 'কারণ',
        'col_note' => 'মন্তব্য',
        'col_user' => 'ব্যবহারকারী',
    ],
    'flash' => [
        'item_added' => 'আইটেম যোগ হয়েছে।',
        'movement_recorded' => 'স্টক খাতায় লেখা হয়েছে।',
        'insufficient_stock' => 'স্টকের চেয়ে বেশি বের করা যাবে না। :detail',
    ],
];
