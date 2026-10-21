<?php

return [

    /** Logo */
    'logo' => '',
    'logo_main' => '',

    'platform' => [
        'panel' => "CRM Panel",
        'app' => "Mobile Application",
    ],

    'web' => [
        'logo' => 'software/img/logo.png',
        'theme_primary_color' => '#000000',
        'theme_primary_color_light' => '#000000',
    ],

    'app' => [
        'logo' => 'software/img/logo.png',
        'theme_primary_color' => '#000000',
        'theme_primary_color_light' => '#000000',
    ],

    'permissions' => [
        'view' => 'view',
        'add' => 'add',
        'update' => 'update',
        'delete' => 'delete',
        'restore' => 'restore',
        'print' => 'print',
        'excel' => 'excel',
        'approval' => 'approval',
        'all_data' => 'all_data',
        'personal_data' => 'personal_data',
    ],

    'app_permissions' => [
        'view' => 'view',
        'add' => 'add',
        'update' => 'update',
        'delete' => 'delete',
        'approval' => 'approval',
        'all_data' => 'all_data',
        'personal_data' => 'personal_data',
    ],

    'quotation_slug' => [
        'pen' => 'Pending',
        'app' => 'Approve',
        'co' => 'Create Order',
        'og' => 'Order Genarated',
    ],

    'sales_order_slug' => [
        'pen' => 'Pending',
        'app' => 'Approve',
        'cp' => 'Create Packing Slip',
        'ps' => 'Partially Packing',
        'fs' => 'Full Packing',
    ],

    'dispatch_order_slug' => [
        'pen' => 'Pending',
    ],

    'packing_slip_slug' => [
        'pen' => 'Pending',
        'cI' => 'Create Invoice',
        'ig' => 'Invoice Generated',
    ],

    'invoice_slug' => [
        'pen' => 'Pending',
        'app' => 'Approve',
    ],

    'grn_slug' => [
        'pen' => 'Pending',
        'app' => 'Approve',
    ],

    'purchase_order_slug' => [
        'pen' => 'Pending',
        'app' => 'Approve',
        'ci' => 'Create Inward',
        'pi' => 'Partially Inward',
        'fi' => 'Full Inward',
    ],

    'job_work_issue_slug' => [
        'pen' => 'Pending',
        'pd' => 'Partially Received',
        'fd' => 'Full Received',
    ],

    'firebase' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'appId' => env('FIREBASE_APP_ID'),
        'vapidKey' => env('FIREBASE_VAPID_KEY'),
    ],

    'order_type' => [
        'kg' => 'KG',
        'pcs' => 'Pcs',
        'gross' => 'Gross',
    ],

    'invoice_type' => [
        'tax' => 'Tax',
        'merchant' => 'Merchant Exporter',
        'SEZ' => 'SEZ',
    ],
];
