<?php

declare(strict_types=1);

return [
    'accepted' => ':attribute গ্রহণ করা আবশ্যক।',
    'confirmed' => ':attribute নিশ্চিতকরণ মিলছে না।',
    'email' => ':attribute অবশ্যই একটি সঠিক ইমেইল ঠিকানা হতে হবে।',
    'max' => [
        'numeric' => ':attribute :max এর বেশি হতে পারে না।',
        'file' => ':attribute এর আকার :max কিলোবাইটের বেশি হতে পারে না।',
        'string' => ':attribute :max অক্ষরের বেশি হতে পারে না।',
        'array' => ':attribute এ :max এর বেশি উপাদান থাকতে পারে না।',
    ],
    'min' => [
        'numeric' => ':attribute কমপক্ষে :min হতে হবে।',
        'file' => ':attribute কমপক্ষে :min কিলোবাইট হতে হবে।',
        'string' => ':attribute কমপক্ষে :min অক্ষরের হতে হবে।',
        'array' => ':attribute এ কমপক্ষে :min টি উপাদান থাকতে হবে।',
    ],
    'numeric' => ':attribute অবশ্যই একটি সংখ্যা হতে হবে।',
    'required' => ':attribute পূরণ করা আবশ্যক।',
    'unique' => 'এই :attribute ইতিপূর্বে ব্যবহৃত হয়েছে।',
    'attributes' => [
        'name' => 'নাম',
        'email' => 'ইমেইল',
        'password' => 'পাসওয়ার্ড',
        'phone' => 'মোবাইল নম্বর',
        'mobile' => 'মোবাইল নম্বর',
        'amount' => 'টাকার পরিমাণ',
        'roll_no' => 'রোল নং',
        'date' => 'তারিখ',
    ],
];
