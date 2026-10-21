<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\QRScanController;
use App\Http\Controllers\software\QuickQRGeneratorController;
use App\Models\QRLinkGenerate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

// Auth::routes();
// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Block direct web access to .env and sensitive environment files
Route::any('/.env{any?}', function () {
    abort(404);
})->where('any', '.*');

Route::get('optimize', function () {
    Artisan::call('optimize:clear');
    return Redirect::back()->with('success', 'Optimize the site, cleare all cache');
})->name('optimize');

Route::get('/test-firebase', function () {
    $service = new \App\Services\FirebaseService();
    return 'Firebase Service initialized!';
});

Route::get('/run-cron', function () {
    \Log::info('run-cron on web request ' . now());
    Artisan::call('schedule:run');
    return "Run cron command";
});

/** CRM Software / Master Routes */
Route::group(['prefix' => 'software'], function () {
    include  base_path("routes/software.php");
});


// Route::get('/scan-form/{id}', [QuickQRGeneratorController::class, 'scanForm'])->name('scan.form');
Route::match(['get', 'post'], '{company_slug}/redemption/{redemption_code_label}', [QuickQRGeneratorController::class, 'guest_scanner'])->name('geast.user.scanner');

Route::match(['get', 'post'], 'thank-you', function () {
    return view('thankyou');
})->name('geast.thankyou');


Route::get('/nimit', function () {
    return view('welcome');
});

Route::get('/testing/map', function () {
    $coordinates = [
        ['lat' => 22.254813, 'lng' => 70.788579],
        ['lat' => 22.256449, 'lng' => 70.788464],
        ['lat' => 22.257347, 'lng' => 70.786675],
        ['lat' => 22.260689, 'lng' => 70.781521],
    ];

    return view('testing.map', compact('coordinates'));
});

Route::get('/checkSendMail', function () {
    $folder = "uploads/4-ocean-software/quotations/2025-05/";
    $filename = "Quotation_29_2025-05-30_11-18-34.pdf";
    $attachmentPath = $folder . '/' . $filename;
    Helper::applyDynamicMailConfig(1);
    $toMail = [];
    $toMail[] = "nimit.ocean@gmail.com";
    $toMail[] = "chandani1994.oceaninfotech@gmail.com";

    $response = Mail::to("nimit.ocean@gmail.com")->send(
        new GenericMail(
            'Your Subject',
            '<h1>Body content nimit</h1>'
            // [
            //     [
            //         'file' => $attachmentPath,
            //         'options' => [
            //             'as' => 'Quotation_29.pdf',
            //             'mime' => 'application/pdf',
            //         ]
            //     ]
            // ]
        )
    );
    dd("L-85", Config::get('mail.mailers.smtp'), $response);
});



Route::get('/dashboard', function () {
    return redirect('software/dashboard', 301);
});

Route::get('/send-test-notification/{userId}', function($userId) {
    \Illuminate\Support\Facades\Log::info('🧪 TEST ROUTE CALLED', ['user_id' => $userId]);

    $user = \App\Models\TeamPerson::find($userId);

    if (!$user || !$user->device_token) {
        return response()->json([
            'error' => 'User not found or no device token',
            'user_id' => $userId,
            'token' => $user->device_token ?? 'NULL'
        ]);
    }

    // Create a test notification in database first
    $notification = \App\Models\Notification::create([
        'user_id' => $user->id,
        'user_type' => 'Team',
        'title' => '🧪 Test Notification',
        'body' => 'Test sent at ' . now()->format('H:i:s') . ' - Click Completed or Decline',
        'module_name' => 'Test',
        'module_id' => 999,
        'module_action' => 'created',
        'notify_read' => 0,
        'status' => 'info',
        'created_by' => 1,
        'created_type' => 'admin',
        'send_status' => 'pending',
    ]);

    // Send notification with action buttons
    try {
        $firebase = app(\App\Services\FirebaseService::class);

        $data = [
            'notification_id' => (string)$notification->id,
            'module_id' => '999',
            'module_name' => 'Test',
            'test' => 'true',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        $results = $firebase->sendNotificationWithActions(
            [$user->device_token],
            '🧪 Test Notification',
            'Test sent at ' . now()->format('H:i:s') . ' - Click Completed or Decline',
            $data
        );

        foreach ($results as $result) {
            $notification->send_status = $result['status'] === 'success' ? 'success' : 'failed';
            $notification->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification sent! Check browser for popup with buttons',
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'token_preview' => substr($user->device_token, 0, 30) . '...',
            'results' => $results
        ]);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Test notification failed', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('web');

// Route::get('qr/link/{uuid}', function ($uuid) {
//     $links = [
//         'RMAndroidApplication' => 'https://play.google.com/store/apps/details?id=com.rmengineering.rangelist&pcampaignid=web_share&pli=1',
//     ];

//     if (isset($links[$uuid])) {
//         return redirect()->away($links[$uuid]);
//     }
//     abort(404, 'Link not found');
// })->name('qr_link');

// Route::get('qr/link/{uuid}', function ($uuid) {
//     $record = DB::table('qr_link_generate')
//         ->where('qr_code_uuid', $uuid)
//         ->whereNull('deleted_at')
//         ->first();

//     if (!$record) {
//         abort(404, 'QR Code not found');
//     }

//     DB::table('qr_link_scans')->insert([
//         'qr_link_id' => $record->id,
//         'ip_address' => request()->ip(),
//         'user_agent' => request()->userAgent(),
//         'created_at' => now(),
//         'updated_at' => now(),
//     ]);

//     DB::table('qr_link_generate')->where('id', $record->id)->increment('scan_count');
//     $fields = json_decode($record->qr_type_fields, true);
//     $type = QRLinkGenerate::QR_Types($record->qr_type_id);
//     $type = $type->slug;

//     switch ($type) {
//         case 'link':
//             if (!empty($fields['link'])) {
//                 return redirect()->away($fields['link']);
//             }
//             break;

//         case 'text':
//             return response()->make($fields['text'] ?? 'No text found', 200, [
//                 'Content-Type' => 'text/plain'
//             ]);

//         case 'email':
//             if (!empty($fields['email_to'])) {
//                 $subject = urlencode($fields['subject'] ?? '');
//                 $body    = urlencode($fields['body'] ?? '');
//                 return redirect()->away("mailto:{$fields['email_to']}?subject={$subject}&body={$body}");
//             }
//             break;

//         case 'whatsapp':
//             if (!empty($fields['phone'])) {
//                 $msg = urlencode($fields['message'] ?? '');
//                 return redirect()->away("https://wa.me/{$fields['phone']}?text={$msg}");
//             }
//             break;

//         case 'wifi':
//             // Just show formatted wifi string (to connect manually)
//             $wifi = "WIFI:SSID:{$fields['ssid']};Encryption:{$fields['encryption']};Password:{$fields['password']};;";
//             return response()->make($wifi, 200, [
//                 'Content-Type' => 'text/plain'
//             ]);

//         case 'image':
//             if (!empty($fields['image']) && file_exists(public_path($fields['image']))) {
//                 return response()->file(public_path($fields['image']));
//             }
//             break;

//         case 'pdf':
//             if (!empty($fields['pdf']) && file_exists(public_path($fields['pdf']))) {
//                 return response()->file(public_path($fields['pdf']));
//             }
//             break;

//         case 'vcard':
//             $vcard  = "BEGIN:VCARD\r\n";
//             $vcard .= "VERSION:3.0\r\n";

//             $firstName = $fields['first_name'] ?? '';
//             $lastName  = $fields['last_name'] ?? '';
//             $fullName  = trim($firstName . ' ' . $lastName);
//             $vcard    .= "FN:{$fullName}\r\n";
//             $vcard    .= "N:{$lastName};{$firstName};;;\r\n";

//             if (!empty($fields['company'])) {
//                 $vcard .= "ORG:{$fields['company']}\r\n";
//             }
//             if (!empty($fields['job_title'])) {
//                 $vcard .= "TITLE:{$fields['job_title']}\r\n";
//             }
//             if (!empty($fields['dob'])) {
//                 $vcard .= "BDAY:{$fields['dob']}\r\n";
//             }
//             if (!empty($fields['phone'])) {
//                 $vcard .= "TEL;TYPE=cell,voice:{$fields['phone']}\r\n";
//             }
//             if (!empty($fields['email'])) {
//                 $vcard .= "EMAIL;TYPE=work:{$fields['email']}\r\n";
//             }
//             if (!empty($fields['website'])) {
//                 $vcard .= "URL:{$fields['website']}\r\n";
//             }

//             if (!empty($fields['address'])) {
//                 $vcard .= "ADR;TYPE=home:;;{$fields['address']};;;;\r\n";
//             }

//             if (!empty($fields['profile_image'])) {
//                 $photoBase64 = chunk_split(base64_encode(file_get_contents($fields['profile_image'])));
//                 $vcard .= "PHOTO;ENCODING=b;TYPE=JPEG:{$photoBase64}\r\n";
//             }

//             if (!empty($fields['card_color'])) {
//                 $vcard .= "X-CARD-COLOR:{$fields['card_color']}\r\n";
//             }

//             $vcard .= "END:VCARD\r\n";

//             return response($vcard, 200, [
//                 'Content-Type'        => 'text/vcard',
//                 'Content-Disposition' => 'inline; filename="contact.vcf"'
//             ]);

//         case 'phonecall':
//             if (!empty($fields['phone'])) {
//                 return redirect()->away("tel:{$fields['phone']}");
//             }
//             break;

//         case 'map':
//             if (!empty($fields['latitude']) && !empty($fields['longitude'])) {
//                 return redirect()->away("https://www.google.com/maps?q={$fields['latitude']},{$fields['longitude']}");
//             } elseif (!empty($fields['map_link'])) {
//                 return redirect()->away($fields['map_link']);
//             }
//             break;

//         case 'audio':
//             if (!empty($fields['audio']) && file_exists(public_path($fields['audio']))) {
//                 return response()->file(public_path($fields['audio']));
//             }
//             break;

//         case 'facebook':
//             if (!empty($fields['facebook_url'])) {
//                 return redirect()->away($fields['facebook_url']);
//             }
//             break;

//         case 'instagram':
//             if (!empty($fields['instagram_url'])) {
//                 return redirect()->away($fields['instagram_url']);
//             }
//             break;

//         case 'youtube':
//             if (!empty($fields['youtube_url'])) {
//                 return redirect()->away($fields['youtube_url']);
//             }
//             break;

//         case 'telegram':
//             if (!empty($fields['telegram_url'])) {
//                 return redirect()->away($fields['telegram_url']);
//             }
//             break;

//         case 'appstore':
//             $userAgent = request()->header('User-Agent');

//             if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
//                 if (!empty($fields['app_store_link'])) {
//                     return redirect()->away($fields['app_store_link']);
//                 }
//             } elseif (stripos($userAgent, 'Android') !== false) {
//                 if (!empty($fields['google_play_link'])) {
//                     return redirect()->away($fields['google_play_link']);
//                 }
//             }

//             if (!empty($fields['other_device_link'])) {
//                 return redirect()->away($fields['other_device_link']);
//             }
//         case 'tiktok':
//             if (!empty($fields['tiktok_url'])) {
//                 return redirect()->away($fields['tiktok_url']);
//             }
//             break;
//         case 'video':
//             if (!empty($fields['video_url'])) {
//                 return redirect()->away($fields['video_url']);
//             }
//             break;
//         case 'googleform':
//             if (!empty($fields['googleform_url'])) {
//                 return redirect()->away($fields['googleform_url']);
//             }
//             break;
//         case 'twitter':
//             if (!empty($fields['twitter_url'])) {
//                 return redirect()->away($fields['twitter_url']);
//             }
//             break;
//         case 'snapchat':
//             if (!empty($fields['snapchat_url'])) {
//                 return redirect()->away($fields['snapchat_url']);
//             }
//             break;
//         case 'spotify':
//             if (!empty($fields['spotify_url'])) {
//                 return redirect()->away($fields['spotify_url']);
//             }
//             break;
//         case 'googledoc':
//             if (!empty($fields['googledoc_url'])) {
//                 return redirect()->away($fields['googledoc_url']);
//             }
//             break;
//         case 'googlereview':
//             if (!empty($fields['googlereview_url'])) {
//                 return redirect()->away($fields['googlereview_url']);
//             }
//             break;
//         case 'googlesheet':
//             if (!empty($fields['googlesheet_url'])) {
//                 return redirect()->away($fields['googlesheet_url']);
//             }
//             break;
//         case 'payment':
//             if (!empty($fields['payment_url'])) {
//                 return redirect()->away($fields['payment_url']);
//             }
//             break;
//         case 'sms':
//             if (!empty($fields['phone'])) {
//                 $smsUri = "sms:{$fields['phone']}";

//                 if (!empty($fields['message'])) {
//                     $smsUri .= "?body=" . urlencode($fields['message']);
//                 }
//                 return redirect()->away($smsUri);
//             }
//             break;
//         case 'logo':
//             if (!empty($fields['logo_url'])) {
//                 return redirect()->away($fields['logo_url']);
//             }
//             break;
//         case 'office365':
//             if (!empty($fields['office365_url'])) {
//                 return redirect()->away($fields['office365_url']);
//             }
//             break;
//         case 'shaped':
//             if (!empty($fields['shaped_url'])) {
//                 return redirect()->away($fields['shaped_url']);
//             }
//             break;
//         case 'paypal':
//             if (!empty($fields['paypal_url'])) {
//                 return redirect()->away($fields['paypal_url']);
//             }
//             break;
//         case 'etsy':
//             if (!empty($fields['etsy_url'])) {
//                 return redirect()->away($fields['etsy_url']);
//             }
//             break;
//         case 'png':
//             if (!empty($fields['png_file']) && file_exists(public_path($fields['png_file']))) {
//                 return response()->file(public_path($fields['png_file']));
//             }
//             break;
//         case 'linkedin':
//             if (!empty($fields['linkedin_url'])) {
//                 return redirect()->away($fields['linkedin_url']);
//             }
//             break;
//         case 'crypto':
//             if (!empty($fields['crypto_type']) && !empty($fields['wallet_address'])) {
//                 $cryptoUri = '';

//                 if ($fields['crypto_type'] === 'bitcoin') {
//                     $cryptoUri = "bitcoin:{$fields['wallet_address']}";

//                     if (!empty($fields['crypto_amount'])) {
//                         $cryptoUri .= "?amount={$fields['crypto_amount']}";
//                     }

//                     if (!empty($fields['crypto_message'])) {
//                         $cryptoUri .= (strpos($cryptoUri, '?') !== false ? '&' : '?') . "message=" . urlencode($fields['crypto_message']);
//                     }
//                 }

//                 if ($fields['crypto_type'] === 'ethereum') {
//                     $cryptoUri = "ethereum:{$fields['wallet_address']}";

//                     if (!empty($fields['crypto_amount'])) {
//                         $cryptoUri .= "?value={$fields['crypto_amount']}";
//                     }
//                 }

//                 return redirect()->away($cryptoUri);
//             }
//             break;
//         case 'calendar':
//             if (!empty($fields['calendar_url'])) {
//                 return redirect()->away($fields['calendar_url']);
//             }
//             break;
//         case 'social':
//             if (!empty($fields['social_media_url'])) {
//                 return redirect()->away($fields['social_media_url']);
//             }
//             break;
//         case 'reddit':
//             if (!empty($fields['reddit_url'])) {
//                 return redirect()->away($fields['reddit_url']);
//             }
//             break;
//         case 'file':
//             if (!empty($fields['file']) && file_exists(public_path($fields['file']))) {
//                 return response()->file(public_path($fields['file']));
//             }
//             break;
//         case 'excel':
//             if (!empty($fields['excel']) && file_exists(public_path($fields['excel']))) {
//                 return response()->file(public_path($fields['excel']));
//             }
//             break;
//         case 'venmo':
//             if (!empty($fields['venmo_url'])) {
//                 return redirect()->away($fields['venmo_url']);
//             }
//             break;
//         case 'amazon':
//             if (!empty($fields['amazon_url'])) {
//                 return redirect()->away($fields['amazon_url']);
//             }
//             break;
//         case 'pptx':
//             if (!empty($fields['pptx_file']) && file_exists(public_path($fields['pptx_file']))) {
//                 return response()->file(public_path($fields['pptx_file']));
//             }
//             break;
//         case '2dbarcode':
//             if ($fields['barcode_encode_type'] === 'link' && !empty($fields['product_link'])) {
//                 return redirect()->away($fields['product_link']);
//             } elseif ($fields['barcode_encode_type'] === 'text' && !empty($fields['gtin_code'])) {
//                 return response()->make($fields['gtin_code'], 200, [
//                     'Content-Type' => 'text/plain'
//                 ]);
//             }
//             break;
//         case 'upi':
//             if (!empty($fields['upi_id'])) {
//                 $upiUri = "upi://pay?pa=" . urlencode($fields['upi_id']);

//                 if (!empty($fields['payee_name'])) {
//                     $upiUri .= "&pn=" . urlencode($fields['payee_name']);
//                 }
//                 if (!empty($fields['amount'])) {
//                     $upiUri .= "&am=" . urlencode($fields['amount']);
//                 }
//                 if (!empty($fields['transaction_remark'])) {
//                     $upiUri .= "&tn=" . urlencode($fields['transaction_remark']);
//                 }
//                 $upiUri .= "&cu=" . (!empty($fields['upi_currency']) ? urlencode($fields['upi_currency']) : 'INR');
//                 return redirect()->away($upiUri);
//             }
//             break;
//         case 'pcr':
//             if (!empty($fields['expire_time'])) {
//                 if (now()->gt($fields['expire_time'])) {
//                     return response()->view('qr_expired', [], 410);
//                 }
//                 return redirect()->route('qr.pcr.password', ['id' => $record->id]);
//             }
//             break;
//     }

//     abort(404, 'Link not found');
// })->name('qr_link');

Route::get('qr/link/{uuid}', [QRScanController::class, 'handle'])->name('qr_link');
Route::get('/qr/pcr/{id}/password', [QRScanController::class, 'showPasswordForm'])->name('qr.pcr.password');
Route::post('/qr/pcr/{id}/verify', [QRScanController::class, 'verifyPassword'])->name('qr.pcr.verify');


Route::get('/', function () {
    return redirect('software/dashboard', 301);
});
