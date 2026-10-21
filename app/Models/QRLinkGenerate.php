<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QRLinkGenerate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qr_link_generate';

    protected $fillable = [
        'company_id',
        'qr_type_id',
        'qr_code_uuid',
        'qr_code_image',
        'name',
        'qr_type_fields',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function QR_Types($id = null){
        $QRType = [];
        $QRType[] = (object)['id' => 1, 'name' => 'Link',     'slug' => 'link'];
        $QRType[] = (object)['id' => 2, 'name' => 'Text',     'slug' => 'text'];
        $QRType[] = (object)['id' => 3, 'name' => 'Email',    'slug' => 'email'];
        $QRType[] = (object)['id' => 4, 'name' => 'WhatsApp', 'slug' => 'whatsapp'];
        $QRType[] = (object)['id' => 5, 'name' => 'WiFi',     'slug' => 'wifi'];
        $QRType[] = (object)['id' => 6, 'name' => 'Image',    'slug' => 'image'];
        $QRType[] = (object)['id' => 7, 'name' => 'PDF',      'slug' => 'pdf'];
        $QRType[] = (object)['id' => 8, 'name' => 'vCard', 'slug' => 'vcard'];
        $QRType[] = (object)['id' => 9, 'name' => 'Phone Call', 'slug' => 'phonecall'];
        $QRType[] = (object)['id' => 10, 'name' => 'Map', 'slug' => 'map'];
        $QRType[] = (object)['id' => 11, 'name' => 'Audio', 'slug' => 'audio'];
        $QRType[] = (object)['id' => 12, 'name' => 'Facebook', 'slug' => 'facebook'];
        $QRType[] = (object)['id' => 13, 'name' => 'Instagram', 'slug' => 'instagram'];
        $QRType[] = (object)['id' => 14, 'name' => 'YouTube', 'slug' => 'youtube'];
        $QRType[] = (object)['id' => 15, 'name' => 'Telegram', 'slug' => 'telegram'];
        $QRType[] = (object)['id' => 16, 'name' => 'Play Market / App Store', 'slug' => 'appstore'];


        $QRType[] = (object)['id' => 17, 'name' => 'TikTok',        'slug' => 'tiktok'];
        $QRType[] = (object)['id' => 18, 'name' => 'Video',         'slug' => 'video'];
        $QRType[] = (object)['id' => 19, 'name' => 'Google Form',   'slug' => 'googleform'];
        $QRType[] = (object)['id' => 20, 'name' => 'Twitter (X)',   'slug' => 'twitter'];
        $QRType[] = (object)['id' => 21, 'name' => 'Snapchat',      'slug' => 'snapchat'];
        $QRType[] = (object)['id' => 22, 'name' => 'Spotify',       'slug' => 'spotify'];
        $QRType[] = (object)['id' => 23, 'name' => 'Google Doc',    'slug' => 'googledoc'];
        $QRType[] = (object)['id' => 24, 'name' => 'Google Review', 'slug' => 'googlereview'];
        $QRType[] = (object)['id' => 25, 'name' => 'Google Sheet',  'slug' => 'googlesheet'];
        $QRType[] = (object)['id' => 26, 'name' => 'Payment',       'slug' => 'payment'];
        $QRType[] = (object)['id' => 27, 'name' => 'SMS',           'slug' => 'sms']; // (phone + message)
        $QRType[] = (object)['id' => 28, 'name' => 'Logo',          'slug' => 'logo'];
        $QRType[] = (object)['id' => 29, 'name' => 'Office 365',    'slug' => 'office365'];
        $QRType[] = (object)['id' => 30, 'name' => 'Shaped QR',     'slug' => 'shaped'];
        $QRType[] = (object)['id' => 31, 'name' => 'PayPal',        'slug' => 'paypal'];
        $QRType[] = (object)['id' => 32, 'name' => 'Etsy',          'slug' => 'etsy'];
        $QRType[] = (object)['id' => 33, 'name' => 'PNG',           'slug' => 'png'];
        $QRType[] = (object)['id' => 34, 'name' => 'LinkedIn',      'slug' => 'linkedin'];
        $QRType[] = (object)['id' => 35, 'name' => 'Crypto Payment','slug' => 'crypto'];
        $QRType[] = (object)['id' => 36, 'name' => 'Calendar',      'slug' => 'calendar'];
        $QRType[] = (object)['id' => 37, 'name' => 'Social Media',  'slug' => 'social'];
        $QRType[] = (object)['id' => 38, 'name' => 'Reddit',        'slug' => 'reddit'];
        $QRType[] = (object)['id' => 39, 'name' => 'File',          'slug' => 'file'];
        $QRType[] = (object)['id' => 40, 'name' => 'Excel',         'slug' => 'excel'];
        $QRType[] = (object)['id' => 41, 'name' => 'Venmo Profile', 'slug' => 'venmo'];
        $QRType[] = (object)['id' => 42, 'name' => 'Amazon',        'slug' => 'amazon'];
        $QRType[] = (object)['id' => 43, 'name' => 'PPTX',          'slug' => 'pptx'];
        $QRType[] = (object)['id' => 44, 'name' => '2D-Barcode',    'slug' => '2dbarcode'];
        $QRType[] = (object)['id' => 45, 'name' => 'UPI',           'slug' => 'upi'];
        $QRType[] = (object)['id' => 46, 'name' => 'PCR',           'slug' => 'pcr'];
        // $QRType[] = (object)['id' => 47, 'name' => 'Custom URL',    'slug' => 'customurl'];

        $QRType = collect($QRType);
        if($id){
            return $QRType->firstWhere('id', $id);
        }
        return $QRType;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
