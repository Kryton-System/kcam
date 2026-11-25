<?php

declare(strict_types=1);

return [
    'title' => 'Inventory',
    'index' => [
        'subtitle' => 'School supplies stock',
        'new_item' => 'New item',
        'name' => 'Name',
        'unit' => 'Unit',
        'unit_placeholder' => 'pcs, kg, box',
        'low_stock_level' => 'Low stock level',
        'add' => 'Add',
        'empty_title' => 'No items yet',
        'empty_description' => 'Add your first item above.',
        'col_stock' => 'Stock',
        'col_status' => 'Status',
        'low_stock' => 'Low stock',
        'ok' => 'OK',
        'ledger' => 'Ledger',
    ],
    'show' => [
        'current_stock' => 'Current stock',
        'new_movement' => 'New movement',
        'type' => 'Type',
        'quantity' => 'Quantity',
        'quantity_hint' => 'Adjustments may be negative, e.g. -2',
        'reason' => 'Reason',
        'note' => 'Note',
        'save' => 'Save',
        'ledger_title' => 'Stock ledger',
        'empty_title' => 'No movements yet',
        'empty_description' => 'Record the first movement above.',
        'col_date' => 'Date',
        'col_type' => 'Type',
        'col_qty' => 'Qty',
        'col_reason' => 'Reason',
        'col_note' => 'Note',
        'col_user' => 'User',
    ],
    'flash' => [
        'item_added' => 'Item added.',
        'movement_recorded' => 'Movement recorded.',
        'insufficient_stock' => 'Not enough stock: :detail',
    ],
];
