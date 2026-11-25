<?php

declare(strict_types=1);

return [
    'common' => [
        'route' => 'Route',
        'vehicle' => 'Vehicle',
        'seats' => 'Seats',
        'edit' => 'Edit',
        'print' => 'Print',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'student' => 'Student',
        'class' => 'Class',
        'stop' => 'Stop',
        'stops' => 'Stops',
        'roster' => 'Roster',
        'driver' => 'Driver',
        'mobile' => 'Mobile',
        'no_vehicle' => 'No vehicle',
    ],

    'index' => [
        'title' => 'Transport',
        'subtitle' => 'Routes and vehicles',
        'new_route' => 'New route',
        'new_vehicle' => 'New vehicle',
        'routes' => 'Routes',
        'no_routes' => 'No routes',
        'no_routes_hint' => 'Routes appear here once added.',
        'vehicles' => 'Vehicles',
        'no_vehicles' => 'No vehicles',
        'no_vehicles_hint' => 'Vehicles appear here once added.',
        'van_no' => 'Van no.',
        'capacity' => 'Capacity',
    ],

    'show' => [
        'monthly_total' => 'Monthly total',
        'assign_student' => 'Assign student',
        'choose' => 'Choose',
        'monthly_fee_label' => 'Monthly fee (৳)',
        'assign' => 'Assign',
        'no_students' => 'No students',
        'no_students_hint' => 'Assigned students appear here.',
        'fee' => 'Monthly fee',
        'confirm_remove' => 'Remove this student?',
        'remove' => 'Remove',
    ],

    'print' => [
        'title' => 'Route roster',
        'van' => 'Van',
        'no' => 'No.',
        'total' => 'Total',
    ],

    'route_form' => [
        'title' => 'Route',
        'edit_heading' => 'Edit route',
        'new_heading' => 'New route',
        'name_en' => 'Route (English)',
        'name_bn' => 'Route name (Bangla)',
        'stops' => 'Stops (one per line)',
    ],

    'vehicle_form' => [
        'title' => 'Vehicle',
        'edit_heading' => 'Edit vehicle',
        'new_heading' => 'New vehicle',
        'van_number' => 'Van number',
        'driver_name' => 'Driver name',
        'driver_mobile' => 'Driver mobile',
        'capacity' => 'Capacity (seats)',
    ],

    'flash' => [
        'route_added' => 'Route added.',
        'route_updated' => 'Route updated.',
        'student_assigned' => 'Student assigned.',
        'student_removed' => 'Student removed.',
        'vehicle_added' => 'Vehicle added.',
        'vehicle_updated' => 'Vehicle updated.',
    ],

    'errors' => [
        'no_vehicle' => 'This route has no vehicle yet.',
        'vehicle_full' => 'The vehicle is full.',
        'already_assigned' => 'This student already rides a route.',
        'vehicle_over_capacity' => 'This vehicle would carry :riders riders, more than its :seats seats.',
        'capacity_below_riders' => 'Capacity cannot be below the :riders riders already assigned.',
        'fee_too_high' => 'The monthly fee cannot exceed 40,000,000 taka.',
        'choose_active_student' => 'Choose an active student of the current year.',
    ],
];
