<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'liliya' => [
        'email' => env('LILIYA_EMAIL'),
        'password' => env('LILIYA_PASSWORD'),
        'client_id' => env('LILIYA_CLIENT_ID', 'SHALIB467'),
        'job_work_email' => env('LILIYA_JOB_WORK_EMAIL', env('LILIYA_EMAIL')),
        'job_work_password' => env('LILIYA_JOB_WORK_PASSWORD', env('LILIYA_PASSWORD')),
        'job_work_client_id' => env('LILIYA_JOB_WORK_CLIENT_ID', env('LILIYA_CLIENT_ID', 'SHALIB467')),
        'job_work_template' => env('LILIYA_JOB_WORK_TEMPLATE', 'shalibadra_job_work'),
        'invoice_email' => env('LILIYA_INVOICE_EMAIL', env('LILIYA_EMAIL')),
        'invoice_password' => env('LILIYA_INVOICE_PASSWORD', env('LILIYA_PASSWORD')),
        'invoice_client_id' => env('LILIYA_INVOICE_CLIENT_ID', env('LILIYA_CLIENT_ID', 'SHALIB467')),
        'invoice_template' => env('LILIYA_INVOICE_TEMPLATE', 'shalibadra_invoice'),
        'purchase_order_email' => env('LILIYA_PURCHASE_ORDER_EMAIL', env('LILIYA_EMAIL')),
        'purchase_order_password' => env('LILIYA_PURCHASE_ORDER_PASSWORD', env('LILIYA_PASSWORD')),
        'purchase_order_client_id' => env('LILIYA_PURCHASE_ORDER_CLIENT_ID', env('LILIYA_CLIENT_ID', 'SHALIB467')),
        'purchase_order_template' => env('LILIYA_PURCHASE_ORDER_TEMPLATE', 'shalibhadra_purchase_order'),
    ],
];
