<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Filament Shield Language Lines
    |--------------------------------------------------------------------------
    |
    | Custom overrides for Filament Shield navigation labels
    |
    */

    'nav' => [
        'group' => 'User Management',  // Change from 'Filament Shield' to 'User Management' or empty string ''
        'role' => [
            'label' => 'Roles',         // Change from whatever default to 'Roles'
            'icon' => 'heroicon-o-shield-check',
        ],
    ],

    // Keep the rest of the translations as defaults
    'resource' => [
        'label' => [
            'role' => 'Role',
            'roles' => 'Roles',
        ],
    ],

    'section' => [
        'permission' => 'Permissions',
    ],

    'column' => [
        'name' => 'Name',
        'guard_name' => 'Guard',
        'permissions' => 'Permissions',
        'team' => 'Team',
        'updated_at' => 'Updated At',
    ],

    'field' => [
        'name' => 'Name',
        'guard_name' => 'Guard Name',
        'team' => 'Teams',
        'team.placeholder' => 'Select Teams',
        'select_all' => [
            'name' => 'Select All',
            'message' => 'Enable all Permissions for this role',
        ],
    ],

    'forbidden' => 'Forbidden',

    'resource_permission_prefixes_labels' => [
        'view' => 'View',
        'view_any' => 'View Any',
        'create' => 'Create',
        'update' => 'Update',
        'restore' => 'Restore',
        'restore_any' => 'Restore Any',
        'replicate' => 'Replicate',
        'reorder' => 'Reorder',
        'delete' => 'Delete',
        'delete_any' => 'Delete Any',
        'force_delete' => 'Force Delete',
        'force_delete_any' => 'Force Delete Any',
    ],
];