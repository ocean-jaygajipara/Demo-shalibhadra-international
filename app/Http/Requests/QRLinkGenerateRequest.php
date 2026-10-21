<?php

namespace App\Http\Requests;

use App\Models\QRLinkGenerate;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QRLinkGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('qr-link-generate') ?? 0;
        $qr_type = null;
        if ($request?->qr_type_id) {
            $qr_type = QRLinkGenerate::QR_Types($request?->qr_type_id);
        }

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'name' => 'nullable|string|max:255',
            'status' => [
                'required',
                'in:active,inactive'
            ],
        ];

        if (!$qr_type) {
            $rules = [];
            $rules['qr_type_id'] = ['required'];
            return $rules;
        }

        if ($qr_type) {
            if ($qr_type?->slug && $qr_type?->slug == "link") {
                $rules['link'] = ['required', 'url'];
            } elseif ($qr_type?->slug == "text") {
                $rules['text'] = ['required', 'string'];

            } elseif ($qr_type?->slug == "email") {
                $rules['email_to'] = ['required', 'email'];
                $rules['subject']  = ['required', 'string', 'max:255'];
                $rules['body']     = ['required', 'string'];

            } elseif ($qr_type?->slug == "whatsapp") {
                $rules['phone_whatsapp']   = ['required', 'digits_between:10,20'];
                $rules['message_whatsapp'] = ['required', 'string'];

            } elseif ($qr_type?->slug == "wifi") {
                $rules['ssid']       = ['required', 'string', 'max:100'];
                $rules['password']   = ['required', 'string', 'max:100'];
                $rules['encryption'] = ['required', 'in:WEP,WPA,nopass'];

            } elseif ($qr_type?->slug == "pdf") {
                $rules['pdf'] = ['required', 'file', 'mimes:pdf', 'max:5120']; // 5MB limit

            } elseif ($qr_type?->slug == "image") {
                $rules['image'] = ['required', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120']; // 5MB limit

            } elseif ($qr_type?->slug == "vcard") {
                $rules['first_name'] = ['required', 'string', 'max:100'];
                $rules['last_name']  = ['required', 'string', 'max:100'];
                $rules['phone_vcard']      = ['required', 'digits_between:10,20'];
                $rules['email']      = ['required', 'email'];
                $rules['dob']        = ['nullable', 'date'];
                $rules['company']    = ['required', 'string', 'max:255'];
                $rules['job_title']  = ['nullable', 'string', 'max:255'];
                $rules['website']    = ['nullable', 'url'];
                $rules['address']    = ['required'];

            } elseif ($qr_type?->slug == "phonecall") {
                $rules['phone_call'] = ['required', 'digits_between:10,20'];

            } elseif ($qr_type?->slug == "map") {
                $rules['latitude']  = ['required', 'numeric', 'between:-90,90'];
                $rules['longitude'] = ['required', 'numeric', 'between:-180,180'];
                $rules['map_link']  = ['nullable', 'url'];

            } elseif ($qr_type?->slug == "audio") {
                $rules['audio'] = ['required', 'file', 'mimes:mp3,wav,ogg', 'max:10240']; // 10MB

            } elseif ($qr_type?->slug == "facebook") {
                $rules['facebook_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "instagram") {
                $rules['instagram_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "youtube") {
                $rules['youtube_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "telegram") {
                $rules['telegram_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "appstore") {
                $rules['app_store_link'] = ['required', 'url'];
                $rules['google_play_link'] = ['required', 'url'];
                $rules['other_device_link'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "tiktok") {
                $rules['tiktok_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "video") {
                $rules['video_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "googleform") {
                $rules['googleform_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "twitter") {
                $rules['twitter_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "snapchat") {
                $rules['snapchat_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "spotify") {
                $rules['spotify_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "googledoc") {
                $rules['googledoc_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "googlereview") {
                $rules['googlereview_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "googlesheet") {
                $rules['googlesheet_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "payment") {
                $rules['payment_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "sms") {
                $rules['phone_sms']   = ['required', 'digits_between:10,20'];
                $rules['phone_message'] = ['required', 'string'];

            } elseif ($qr_type?->slug == "logo") {
                $rules['logo_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "office365") {
                $rules['office365_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "shaped") {
                $rules['shaped_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "paypal") {
                $rules['paypal_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "etsy") {
                $rules['etsy_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "png") {
                $rules['png_file'] = ['required', 'file', 'mimes:png', 'max:5120'];

            } elseif ($qr_type?->slug == "linkedin") {
                $rules['linkedin_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "crypto") {
                $rules['crypto_type']     = ['required', 'in:bitcoin,ethereum'];
                $rules['crypto_amount']   = ['nullable', 'numeric', 'min:0'];
                $rules['wallet_address']  = ['required', 'string', 'max:255'];
                $rules['crypto_message']  = ['nullable', 'string', 'max:500'];

            } elseif ($qr_type?->slug == "calendar") {
                $rules['calendar_url']   = ['required', 'url'];

            } elseif ($qr_type?->slug == "social") {
                $rules['social_media_url']  = ['required', 'url'];

            } elseif ($qr_type?->slug == "reddit") {
                $rules['reddit_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "file") {
                $rules['file'] = ['required', 'file', 'max:10240']; // 10MB any file

            } elseif ($qr_type?->slug == "excel") {
                $rules['excel'] = ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']; // 5MB

            } elseif ($qr_type?->slug == "venmo") {
                $rules['venmo_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "amazon") {
                $rules['amazon_url'] = ['required', 'url'];

            } elseif ($qr_type?->slug == "pptx") {
                $rules['pptx_file'] = ['required', 'file', 'max:10240']; // 10MB any file

            } elseif ($qr_type?->slug == "2dbarcode") {

                $rules['qr_type_fields.gtin_code'] = [
                    'required,text',
                    'string',
                    'max:50'
                ];
                $rules['barcode_encode_type'] = ['required', 'in:link,text'];

                // if link option selected → product_link required
                $rules['qr_type_fields.product_link'] = [
                    'required_if:barcode_encode_type,link',
                    'nullable',
                    'url'
                ];
            } elseif ($qr_type?->slug == "upi") {
                $rules['qr_type_fields.upi_id'] = [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[\w\.\-]+@[\w]+$/i' // basic UPI ID format like name@bank
                ];

                $rules['qr_type_fields.upi_currency'] = [
                    'required',
                    'in:INR'
                ];

                $rules['qr_type_fields.payee_name'] = [
                    'required',
                    'string',
                    'max:100'
                ];

                $rules['qr_type_fields.upi_amount'] = [
                    'nullable',
                    'numeric',
                    'min:1',
                    'max:999999.99'
                ];

                $rules['qr_type_fields.upi_remark'] = [
                    'nullable',
                    'string',
                    'max:150'
                ];

            } elseif ($qr_type?->slug == "pcr") {
                $rules['pcr_file']  = ['required', 'file', 'max:5120'];
                $rules['expire_time'] = ['required', 'date'];
                $rules['pcr_password']  = ['required', 'string','min:6'];

            } elseif ($qr_type?->slug == "customurl") {

                $rules['default_link'] = ['required', 'url'];

                if ($request->has('locations') && is_array($request->locations)) {
                    foreach ($request->locations as $index => $location) {
                        $rules["locations.$index.country"] = ['required', 'string', 'max:100'];
                        $rules["locations.$index.state"]   = ['required', 'string', 'max:100'];
                        $rules["locations.$index.city"]    = ['required', 'string', 'max:100'];
                        $rules["locations.$index.url"]     = ['required', 'url'];
                    }
                }
            }else {
                dd("requestFile L-48", $rules, $qr_type, $request->all());
            }
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company does not exist.',
            'name.string' => 'The name must be a valid string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'qr_type_id.required' => 'The QR Type field is required.',
        ];
    }
}
