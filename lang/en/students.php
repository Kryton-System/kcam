<?php

declare(strict_types=1);

return [
    'errors' => [
        'invalid_mobile' => 'Enter a valid mobile number, for example 017XXXXXXXX.',
    ],

    'flash' => [
        'admitted' => 'Student admitted successfully.',
        'updated' => 'Student details updated.',
    ],

    'religions' => [
        'islam' => 'Islam',
        'hinduism' => 'Hinduism',
        'buddhism' => 'Buddhism',
        'christianity' => 'Christianity',
        'other' => 'Other',
    ],

    'genders' => [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
    ],

    'relations' => [
        'father' => 'Father',
        'mother' => 'Mother',
        'other' => 'Other guardian',
    ],

    'create' => [
        'section_basic' => '1. Basic information',
        'section_academic' => '2. Academic information',
        'section_contact' => '4. Contact information',
        'section_address' => '5. Address information',
        'section_documents' => '6. Documents and photograph',
        'required_hint' => 'Required field',
        'guardian_mobile' => 'Mobile number for SMS and fee notices',
        'title' => 'New student admission',
        'subtitle' => 'Enter the student details and the guardian contact information',
        'back' => '← Back to list',
        'submit' => 'Complete admission',
        'cancel' => 'Cancel',
        'section_student' => '1. Student information',
        'section_class' => '2. Class and section',
        'section_guardian' => '3. Guardian information',
        'select' => 'Select',
        'select_class' => 'Select class',
        'optional_section' => 'Section (optional)',
    ],

    'fields' => [
        'name' => 'Full name (English)',
        'name_bn' => 'Full name (Bangla)',
        'dob' => 'Date of birth',
        'gender' => 'Gender',
        'blood_group' => 'Blood group',
        'religion' => 'Religion',
        'address' => 'Present address',
        'birth_reg_no' => 'BDRIS birth registration number (BRN)',
        'photo' => 'Student photo',
        'class' => 'Class',
        'section' => 'Section',
        'roll_no' => 'Roll no',
        'guardian_name' => 'Guardian name',
        'guardian_relation' => 'Relation',
        'guardian_mobile' => 'Mobile number (SMS will be sent here)',
        'guardian_nid' => 'Guardian NID number',
        'guardian_nid_front' => 'Guardian NID scan (front)',
        'guardian_nid_back' => 'Guardian NID scan (back)',
    ],

    'hints' => [
        'birth_reg_no' => '17-digit BDRIS birth registration number. Leave blank if not yet registered.',
        'photo' => 'JPG, PNG or WebP, up to 2 MB.',
        'guardian_nid' => '10, 13 or 17 digits.',
        'guardian_nid_scan' => 'JPG, PNG or PDF, up to 2 MB each.',
    ],

    'placeholders' => [
        'name' => 'For example: Sabbir Hossain',
        'name_bn' => 'For example: সাব্বির হোসেন',
        'guardian_name' => 'Father or mother name',
        'mobile' => '017XXXXXXXX',
    ],
    'show' => [
        'list' => 'Student list',
        'edit' => 'Edit',
        'section_student' => 'Student',
        'section_guardian' => 'Guardian',
        'no_photo' => 'No photo',
        'nid_copies' => 'Guardian NID copies',
        'view_front' => 'View front',
        'view_back' => 'View back',
    ],

    'index' => [
        'title' => 'Student list',
        'subtitle' => 'Details and list of all admitted students',
        'new_admission' => 'New admission',
        'search_placeholder' => 'Search by name, student ID or BDRIS number...',
        'all_statuses' => 'All statuses',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'sort_name' => 'Sort: name',
        'sort_code' => 'Sort: student ID',
        'sort_newest' => 'Sort: newest first',
        'all_classes' => 'All classes',
        'filter' => 'Filter',
        'reset' => 'Reset',
        'empty_title' => 'No students found',
        'empty_description' => "To add a new student, click the 'New admission' button above.",
        'admit_student' => 'Admit a student',
        'columns' => [
            'roll' => 'Roll',
            'id' => 'ID',
            'name' => 'Name',
            'class_section' => 'Class and section',
            'guardian_mobile' => 'Guardian mobile',
            'status' => 'Status',
        ],
    ],

    'edit' => [
        'keep_current' => 'Leave empty to keep the current file.',
        'title' => 'Edit student details',
        'page_title' => 'Edit',
        'back' => '← Back',
        'cancel' => 'Cancel',
        'save' => 'Save',
    ],
];
