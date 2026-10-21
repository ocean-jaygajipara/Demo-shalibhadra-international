<?php


namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Inquiry;
use App\Models\QRLinkGenerate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class QRScanController extends Controller
{
    public function handle($uuid)
    {

        $record = DB::table('qr_link_generate')
        ->where('qr_code_uuid', $uuid)
        ->whereNull('deleted_at')
        ->first();

        if (!$record) {
            abort(404, 'QR Code not found');
        }

        DB::table('qr_link_scans')->insert([
            'qr_link_id' => $record->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('qr_link_generate')->where('id', $record->id)->increment('scan_count');
        $fields = json_decode($record->qr_type_fields, true);
        $type = QRLinkGenerate::QR_Types($record->qr_type_id);
        $type = $type->slug;

        switch ($type) {
            case 'link':
                if (!empty($fields['link'])) {
                    return redirect()->away($fields['link']);
                }
                break;

            case 'text':
                return response()->make($fields['text'] ?? 'No text found', 200, [
                    'Content-Type' => 'text/plain'
                ]);

            case 'email':
                if (!empty($fields['email_to'])) {
                    $subject = urlencode($fields['subject'] ?? '');
                    $body    = urlencode($fields['body'] ?? '');
                    return redirect()->away("mailto:{$fields['email_to']}?subject={$subject}&body={$body}");
                }
                break;

            case 'whatsapp':
                if (!empty($fields['phone_whatsapp'])) {
                     $msg = $fields['message_whatsapp'];

                    // Convert <br> or <br/> into newline
                    $msg = preg_replace('/<br\s*\/?>/i', "\n", $msg);

                    // Remove <p> tags, add newline after closing </p>
                    $msg = preg_replace('/<p[^>]*>/i', '', $msg);
                    $msg = preg_replace('/<\/p>/i', "\n", $msg);

                    // Decode HTML entities (&nbsp;)
                    $msg = html_entity_decode($msg, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    // Remove any remaining tags just in case
                    $msg = strip_tags($msg);

                    // Clean up multiple newlines
                    $msg = preg_replace("/\n+/", "\n", $msg);

                    // Trim and urlencode
                    $msg = urlencode(trim($msg));

                    return redirect()->away("https://wa.me/{$fields['phone_whatsapp']}?text={$msg}");
                }
                break;

            case 'wifi':
                // Just show formatted wifi string (to connect manually)
                $wifi = "WIFI:SSID:{$fields['ssid']};Encryption:{$fields['encryption']};Password:{$fields['password']};;";
                return response()->make($wifi, 200, [
                    'Content-Type' => 'text/plain'
                ]);

            case 'image':
                if (!empty($fields['image']) && file_exists(public_path($fields['image']))) {
                    return response()->file(public_path($fields['image']));
                }
                break;

            case 'pdf':
                if (!empty($fields['pdf']) && file_exists(public_path($fields['pdf']))) {
                    return response()->file(public_path($fields['pdf']));
                }
                break;

            case 'vcard':
                $vcard  = "BEGIN:VCARD\r\n";
                $vcard .= "VERSION:3.0\r\n";

                $firstName = $fields['first_name'] ?? '';
                $lastName  = $fields['last_name'] ?? '';
                $fullName  = trim($firstName . ' ' . $lastName);

                $vcard .= "FN:{$fullName}\r\n";
                $vcard .= "N:{$lastName};{$firstName};;;\r\n";

                if (!empty($fields['company'])) {
                    $vcard .= "ORG:{$fields['company']}\r\n";
                }
                if (!empty($fields['job_title'])) {
                    $vcard .= "TITLE:{$fields['job_title']}\r\n";
                }
                if (!empty($fields['dob'])) {
                    $vcard .= "BDAY:{$fields['dob']}\r\n";
                }
                if (!empty($fields['phone_vcard'])) {
                    $vcard .= "TEL;TYPE=CELL:{$fields['phone_vcard']}\r\n";
                }
                if (!empty($fields['email'])) {
                    $vcard .= "EMAIL;TYPE=WORK:{$fields['email']}\r\n";
                }
                if (!empty($fields['website'])) {
                    $vcard .= "URL:{$fields['website']}\r\n";
                }
                if (!empty($fields['address'])) {
                    $vcard .= "ADR;TYPE=HOME:;;{$fields['address']};;;;\r\n";
                }
                if (!empty($fields['profile_image'])) {
                    $photoBase64 = chunk_split(base64_encode(file_get_contents($fields['profile_image'])));
                    $vcard .= "PHOTO;ENCODING=b;TYPE=JPEG:{$photoBase64}\r\n";
                }

                $vcard .= "END:VCARD";


                return QrCode::size(300)->generate($vcard);

            case 'phonecall':
                if (!empty($fields['phone_call'])) {
                    return redirect()->away("tel:{$fields['phone_call']}");
                }
                break;

            case 'map':
                if (!empty($fields['latitude']) && !empty($fields['longitude'])) {
                    return redirect()->away("https://www.google.com/maps?q={$fields['latitude']},{$fields['longitude']}");
                } elseif (!empty($fields['map_link'])) {
                    return redirect()->away($fields['map_link']);
                }
                break;

            case 'audio':
                if (!empty($fields['audio']) && file_exists(public_path($fields['audio']))) {
                    return response()->file(public_path($fields['audio']));
                }
                break;

            case 'facebook':
                if (!empty($fields['facebook_url'])) {
                    return redirect()->away($fields['facebook_url']);
                }
                break;

            case 'instagram':
                if (!empty($fields['instagram_url'])) {
                    return redirect()->away($fields['instagram_url']);
                }
                break;

            case 'youtube':
                if (!empty($fields['youtube_url'])) {
                    return redirect()->away($fields['youtube_url']);
                }
                break;

            case 'telegram':
                if (!empty($fields['telegram_url'])) {
                    return redirect()->away($fields['telegram_url']);
                }
                break;

            case 'appstore':
                $userAgent = request()->header('User-Agent');

                if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
                    if (!empty($fields['app_store_link'])) {
                        return redirect()->away($fields['app_store_link']);
                    }
                } elseif (stripos($userAgent, 'Android') !== false) {
                    if (!empty($fields['google_play_link'])) {
                        return redirect()->away($fields['google_play_link']);
                    }
                }

                if (!empty($fields['other_device_link'])) {
                    return redirect()->away($fields['other_device_link']);
                }
            case 'tiktok':
                if (!empty($fields['tiktok_url'])) {
                    return redirect()->away($fields['tiktok_url']);
                }
                break;
            case 'video':
                if (!empty($fields['video_url'])) {
                    return redirect()->away($fields['video_url']);
                }
                break;
            case 'googleform':
                if (!empty($fields['googleform_url'])) {
                    return redirect()->away($fields['googleform_url']);
                }
                break;
            case 'twitter':
                if (!empty($fields['twitter_url'])) {
                    return redirect()->away($fields['twitter_url']);
                }
                break;
            case 'snapchat':
                if (!empty($fields['snapchat_url'])) {
                    return redirect()->away($fields['snapchat_url']);
                }
                break;
            case 'spotify':
                if (!empty($fields['spotify_url'])) {
                    return redirect()->away($fields['spotify_url']);
                }
                break;
            case 'googledoc':
                if (!empty($fields['googledoc_url'])) {
                    return redirect()->away($fields['googledoc_url']);
                }
                break;
            case 'googlereview':
                if (!empty($fields['googlereview_url'])) {
                    return redirect()->away($fields['googlereview_url']);
                }
                break;
            case 'googlesheet':
                if (!empty($fields['googlesheet_url'])) {
                    return redirect()->away($fields['googlesheet_url']);
                }
                break;
            case 'payment':
                if (!empty($fields['payment_url'])) {
                    return redirect()->away($fields['payment_url']);
                }
                break;
            case 'sms':
                if (!empty($fields['phone_sms'])) {
                    $smsUri = "sms:{$fields['phone_sms']}";

                    if (!empty($fields['phone_message'])) {
                        $smsUri .= "?body=" . urlencode($fields['phone_message']);
                    }
                    return redirect()->away($smsUri);
                }
                break;
            case 'logo':
                if (!empty($fields['logo_url'])) {
                    return redirect()->away($fields['logo_url']);
                }
                break;
            case 'office365':
                if (!empty($fields['office365_url'])) {
                    return redirect()->away($fields['office365_url']);
                }
                break;
            case 'shaped':
                if (!empty($fields['shaped_url'])) {
                    return redirect()->away($fields['shaped_url']);
                }
                break;
            case 'paypal':
                if (!empty($fields['paypal_url'])) {
                    return redirect()->away($fields['paypal_url']);
                }
                break;
            case 'etsy':
                if (!empty($fields['etsy_url'])) {
                    return redirect()->away($fields['etsy_url']);
                }
                break;
            case 'png':
                if (!empty($fields['png_file']) && file_exists(public_path($fields['png_file']))) {
                    return response()->file(public_path($fields['png_file']));
                }
                break;
            case 'linkedin':
                if (!empty($fields['linkedin_url'])) {
                    return redirect()->away($fields['linkedin_url']);
                }
                break;
            case 'crypto':
                if (!empty($fields['crypto_type']) && !empty($fields['wallet_address'])) {
                    $cryptoUri = '';

                    if ($fields['crypto_type'] === 'bitcoin') {
                        $cryptoUri = "bitcoin:{$fields['wallet_address']}";

                        if (!empty($fields['crypto_amount'])) {
                            $cryptoUri .= "?amount={$fields['crypto_amount']}";
                        }

                        if (!empty($fields['crypto_message'])) {
                            $cryptoUri .= (strpos($cryptoUri, '?') !== false ? '&' : '?') . "message=" . urlencode($fields['crypto_message']);
                        }
                    }

                    if ($fields['crypto_type'] === 'ethereum') {
                        $cryptoUri = "ethereum:{$fields['wallet_address']}";

                        if (!empty($fields['crypto_amount'])) {
                            $cryptoUri .= "?value={$fields['crypto_amount']}";
                        }
                    }

                    return redirect()->away($cryptoUri);
                }
                break;
            case 'calendar':
                if (!empty($fields['calendar_url'])) {
                    return redirect()->away($fields['calendar_url']);
                }
                break;
            case 'social':
                if (!empty($fields['social_media_url'])) {
                    return redirect()->away($fields['social_media_url']);
                }
                break;
            case 'reddit':
                if (!empty($fields['reddit_url'])) {
                    return redirect()->away($fields['reddit_url']);
                }
                break;
            case 'file':
                if (!empty($fields['file']) && file_exists(public_path($fields['file']))) {
                    return response()->file(public_path($fields['file']));
                }
                break;
            case 'excel':
                if (!empty($fields['excel']) && file_exists(public_path($fields['excel']))) {
                    return response()->file(public_path($fields['excel']));
                }
                break;
            case 'venmo':
                if (!empty($fields['venmo_url'])) {
                    return redirect()->away($fields['venmo_url']);
                }
                break;
            case 'amazon':
                if (!empty($fields['amazon_url'])) {
                    return redirect()->away($fields['amazon_url']);
                }
                break;
            case 'pptx':
                if (!empty($fields['pptx_file']) && file_exists(public_path($fields['pptx_file']))) {
                    return response()->file(public_path($fields['pptx_file']));
                }
                break;
            case '2dbarcode':
                if ($fields['barcode_encode_type'] === 'link' && !empty($fields['product_link'])) {
                    return redirect()->away($fields['product_link']);
                } elseif ($fields['barcode_encode_type'] === 'text' && !empty($fields['gtin_code'])) {
                    return response()->make($fields['gtin_code'], 200, [
                        'Content-Type' => 'text/plain'
                    ]);
                }
                break;
            case 'upi':
                if (!empty($fields['upi_id'])) {
                    $upiUri = "upi://pay?pa=" . urlencode($fields['upi_id']);

                    if (!empty($fields['payee_name'])) {
                        $upiUri .= "&pn=" . urlencode($fields['payee_name']);
                    }
                    if (!empty($fields['amount'])) {
                        $upiUri .= "&am=" . urlencode($fields['amount']);
                    }
                    if (!empty($fields['transaction_remark'])) {
                        $upiUri .= "&tn=" . urlencode($fields['transaction_remark']);
                    }
                    $upiUri .= "&cu=" . (!empty($fields['upi_currency']) ? urlencode($fields['upi_currency']) : 'INR');
                    return redirect()->away($upiUri);
                }
                break;
            case 'pcr':
                if (!empty($fields['expire_time'])) {
                    if (now()->gt($fields['expire_time'])) {
                        return response()->view('qr_expired', [], 410);
                    }
                    return redirect()->route('qr.pcr.pcr_password', ['id' => $record->id]);
                }
                break;
        }

        abort(404, 'Link not found');
    }

    public function showPasswordForm($id)
    {
        return view('qr_password', ['qrId' => $id]);
    }

    public function verifyPassword(Request $request, $id)
    {
        $qr = QRLinkGenerate::findOrFail($id);
        $pcrData = json_decode($qr->qr_type_fields, true);

        if ($request->password != $pcrData['password']) {
            return back()->withErrors(['password' => 'Invalid password']);
        }

        if (!empty($pcrData['pcr_file']) && file_exists(public_path($pcrData['pcr_file']))) {
            return response()->file(public_path($pcrData['pcr_file']));
        }
    }
}
