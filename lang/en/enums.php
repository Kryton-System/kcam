<?php

declare(strict_types=1);

return [
    'roles' => [
        'owner' => 'Owner',
        'teacher' => 'Teacher',
        'accountant' => 'Accountant',
        'parent' => 'Parent',
        'super_admin' => 'Super Admin',
    ],
    'student_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'transferred' => 'Transferred',
        'graduated' => 'Graduated',
        'dropped' => 'Dropped',
    ],
    'payment_status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'cancelled' => 'Cancelled',
    ],
    'fee_head_type' => [
        'monthly' => 'Monthly',
        'one_time' => 'One-time',
        'yearly' => 'Yearly',
    ],
    'attendance_status' => [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'excused' => 'Excused',
    ],
    'payment_method' => [
        'cash' => 'Cash',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'rocket' => 'Rocket',
        'card' => 'Card',
        'bank_transfer' => 'Bank transfer',
    ],
];
