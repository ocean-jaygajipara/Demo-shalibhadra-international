<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));

        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($tokens, $title, $body)
    {
        $notification = Notification::create($title, $body);
        $message = CloudMessage::new()->withNotification($notification);

        $results = [];
         //dd($notification);

        foreach ($tokens as $token) {
            try {
                $response = $this->messaging->send($message->withChangedTarget('token', $token));
                $results[] = [
                    'token' => $token,
                    'status' => 'success',
                    'response' => $response,
                ];
            } catch (MessagingException $e) {
                Log::error('Firebase Messaging Error: ' . $e->getMessage(), ['token' => $token]);

                $results[] = [
                    'token' => $token,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    //this is for testing direct to web with action buttons
    // public function sendNotification($tokens, $title, $body, $data = [])
    // {

    //     $results = [];

    //     foreach ($tokens as $token) {
    //         try {
    //             // Build message with action buttons
    //             $messageData = [
    //                 'token' => $token,
    //                 'notification' => [
    //                     'title' => $title,
    //                     'body' => $body,
    //                 ],
    //                 'data' => array_map('strval', $data),
    //                 'webpush' => [
    //                     'notification' => [
    //                         'title' => $title,
    //                         'body' => $body,
    //                         'icon' => url('/firebase-logo.png'),
    //                         'requireInteraction' => true,
    //                         'tag' => 'notification-' . ($data['notification_id'] ?? time()),
    //                         'actions' => [
    //                             [
    //                                 'action' => 'completed',
    //                                 'title' => '✓ Completed',
    //                                 'icon' => url('/icons/check.png')
    //                             ],
    //                             [
    //                                 'action' => 'decline',
    //                                 'title' => '✗ Decline',
    //                                 'icon' => url('/icons/close.png')
    //                             ]
    //                         ],
    //                         'data' => [
    //                             'notification_id' => $data['notification_id'] ?? '',
    //                             'module_id' => $data['module_id'] ?? '',
    //                             'module_name' => $data['module_name'] ?? '',
    //                         ]
    //                     ],
    //                     'fcm_options' => [
    //                         'link' => url('/software/dashboard')
    //                     ]
    //                 ]
    //             ];

    //             $message = CloudMessage::fromArray($messageData);
    //             $response = $this->messaging->send($message);

    //             $results[] = [
    //                 'token' => $token,
    //                 'status' => 'success',
    //                 'response' => $response,
    //             ];

    //         } catch (\Exception $e) {

    //             $results[] = [
    //                 'token' => $token,
    //                 'status' => 'failed',
    //                 'error' => $e->getMessage(),
    //             ];
    //         }
    //     }

    //     return $results;
    // }

    // In FirebaseService.php
    public function sendNotificationWithActions($tokens, $title, $body, $data = [])
    {
        $results = [];

        foreach ($tokens as $token) {
            try {
                $message = [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'webpush' => [
                        'fcm_options' => [
                            'link' => url('/notifications')
                        ],
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                            'icon' => '/firebase-logo.png',
                            'requireInteraction' => true,
                            'actions' => [
                                [
                                    'action' => 'completed',
                                    'title' => 'Completed',
                                    'icon' => '/icons/check.png'
                                ],
                                [
                                    'action' => 'decline',
                                    'title' => 'Ignore',
                                    'icon' => '/icons/close.png'
                                ]
                            ]
                        ]
                    ]
                ];

                $response = $this->messaging->send($message);
                $results[] = ['token' => $token, 'status' => 'success'];
            } catch (\Exception $e) {
                $results[] = ['token' => $token, 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
