<?php

namespace App\Helpers;

use App\Models\Company;
use App\Models\SalesStatus;
use App\Models\EmailLog;
use App\Models\MainMenu;
use App\Models\ManageEmail;
use App\Models\ProductStockHistory;
use App\Models\MasterModules;
use App\Models\RolePermission;
use App\Models\JobWorkIssue;
use App\Models\ProductCombo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use App\Mail\GenericMail;
use App\Models\AccountLedger;
use App\Models\AdminSoftware;
use App\Models\Notification;
use App\Models\RolePermissionsApp;
use App\Models\SubMenu;
use App\Models\TeamPerson;
use App\Models\Invoice;
use App\Models\JobWorkReceive;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Intervention\Image\Facades\Image;

class Helper
{
    static function randomToken()
    {
        return sha1(md5(time()) . time() . rand());
    }

    //For Validation Message
    static function validation_message($message, $keys = array())
    {
        return $message->all()[0];
    }

    static function make_slug(string $string)
    {
        return Str::slug(strtolower($string));
    }

    static function getLoginUser($guard = null)
    {
        if (!empty($guard)) {
            return Auth::guard($guard)?->user();
        }
        return Auth::user();
    }

    static function getLoginUserRole()
    {
        $login_user_role = Auth::user()->getRoleNames()->first();
        return $login_user_role;
    }

    static function convert_date($dateString = "", $current_format = 'd/m/Y', $new_format = 'd/m/Y')
    {
        if ($dateString == "") {
            $dateString = \Carbon\Carbon::now();
            return $dateString->format($new_format);
        }
        /*if($current_format == 'Y-m-d H:i:s'){
            return \Carbon\Carbon::createFromFormat($current_format, $dateString)->format($new_format);
        }*/
        return \Carbon\Carbon::createFromFormat($current_format, $dateString)->format($new_format);
    }

    /** Current Date */
    public static function trait_current_date($format = 'Y-m-d H:i:s')
    {
        return Carbon::now()->format($format);
    }

    /** Generate Random String */
    public static function RandomString($length = 40)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $timestamp = now()->format('YmdHis');
        return $timestamp . $randomString;
    }

    /** Existing image convert to webp */
    static function existingImageConverToWebp($extension, $root_path, $file_name, $upload_file_name, $quality = 100, $imagedestroy = false)
    {
        try {
            $file_path = rtrim($root_path, '/') . '/' . $file_name;

            if (!file_exists($file_path)) {
                dd("Helper existingImageConverToWebp NR-108", $extension, $root_path, $file_name, $upload_file_name, $quality, $imagedestroy);
                throw new \Exception("File not found at path: {$file_path}");
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $getFileMimeType = finfo_file($finfo, $file_path); // image/png | image/jpeg | image/jpg
            finfo_close($finfo);

            if (strtolower($getFileMimeType) == 'image/png') {
                $image = @imagecreatefrompng($file_path);
                imagepalettetotruecolor($image);
            } else if (strtolower($getFileMimeType) == "image/jpg" || strtolower($getFileMimeType) == "image/jpeg") {
                $image = imagecreatefromjpeg($file_path);
            } else if (strtolower($getFileMimeType) == "image/webp") {
                $image = imagecreatefromwebp($file_path);
            } else {
                if (strtolower($extension) == 'png') {
                    $image = @imagecreatefrompng($file_path);
                    imagepalettetotruecolor($image);
                } else if (strtolower($extension) == "jpg" || strtolower($extension) == "jpeg") {
                    $image = imagecreatefromjpeg($file_path);
                } else {
                    return '';
                }
            }
            // return
            $upload_file_name = self::make_slug($upload_file_name) . '.webp';
            // dd("L-288", 'exist', $extension, $root_path, $dir, $file_name, $upload_file_name, $quality, $imagedestroy, $getFileMimeType, $image, $upload_file_name);

            imagewebp($image, $root_path . $upload_file_name, $quality);

            // If file is not store in proper folder copy file
            // copy(public_path($upload_file_name), $dir.$upload_file_name);

            // dd("L-295", 'exist', $extension, $root_path, $dir, $file_name, $upload_file_name, $quality, $imagedestroy, $root_path.$upload_file_name . '.webp', $getFileMimeType, $image);
            //delete initial uploaded png image
            if ($imagedestroy) {
                unlink($file_path);
                // imagedestroy($file_path);
            }
            return $upload_file_name;
        } catch (\Exception $e) {
            dd("Helper existingImageConverToWebp NR-106", $e);
            return null;
            dump($e);
        }
        dd("L-320", 'not exist', $root_path, $file_name, $upload_file_name, $imagedestroy);
    }

    /** encryptString */
    static function encryptString($string)
    {
        return Crypt::encryptString($string);
    }

    /** decryptString */
    static function decryptString($string)
    {
        return Crypt::decryptString($string);
    }

    /** Static cURL Request */
    static function cURLRequest($method = "GET", $url, $payload)
    {
        // https://serpapi.com/blog/how-to-use-curl-in-php/#simple-post-request
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            if (ucfirst($method) == "POST") {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, "param1=value1&param2=value2");
            }
            $jsonData = json_encode($payload);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $response = curl_exec($curl);

            curl_close($curl);

            return $response;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /** Check the table exist in the database */
    static function checkTableExist($tableName)
    {
        try {
            return DB::connection()->getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /** Get the table wise get auto increment id */
    static function getTableWiseGetAutoIncrementId($tableName)
    {
        try {
            if (self::checkTableExist($tableName)) {
                $databaseName = DB::getDatabaseName();

                $autoIncrementInfo = DB::table('information_schema.tables')
                    ->where('table_schema', $databaseName)
                    ->where('table_name', $tableName)
                    ->value('AUTO_INCREMENT');

                if ($autoIncrementInfo) {
                    return $autoIncrementInfo;
                } else {
                    return self::RandomString(4);
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get toSQl and bindings
     * ->toSql()
     * ->getBindings()
     * */
    static function interpolateQuery($query, $bindings)
    {
        foreach ($bindings as $binding) {
            $binding = is_numeric($binding) ? $binding : "'$binding'";
            $query = preg_replace('/\?/', $binding, $query, 1);
        }
        return $query;
    }

    /** Get Address by latitude and longitude */
    static function getAddressFromLatLong($latitude, $longitude, $returnResponse = 'address')
    {
        // $addressJson = "https://nominatim.openstreetmap.org/reverse?lat=" . $latitude . "&lon=" . $longitude . "&format=json";
        // Call OpenStreetMap Nominatim API
        $response = Http::get("https://nominatim.openstreetmap.org/reverse", [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
        ]);

        if ($response->successful()) {
            $addressJson = $response->json();

            // $addressJson = (object)$addressJson;
            dd($addressJson);
            if ($returnResponse == "address" && $addressJson?->display_name) {
                return $addressJson?->display_name;
            }
            return $addressJson;
        }
        return null;
    }

    /** Get Main Menu */
    static function getMainMenu($wheres = [], $status = "active", $orderBy = [], $recordType = "all")
    {
        try {
            $main_menu = MainMenu::with(['sub_menu'])->whereNotIn('name', ["Plan Master", "Admin Settings", "Manage Email"]);
            if (count($wheres) > 0) {
                foreach ($wheres as $key => $condition) {
                    // Handle different types of where conditions
                    if (is_array($condition) && isset($condition['type']) && isset($condition['values'])) {
                        // Support for whereIn
                        if ($condition['type'] == 'in' && is_array($condition['values'])) {
                            $main_menu = $main_menu->whereIn($key, $condition['values']);
                        }
                        // Support for custom raw where
                        elseif ($condition['type'] == 'raw') {
                            $main_menu = $main_menu->whereRaw($condition['query'], $condition['bindings'] ?? []);
                        }
                        // Support for other operators
                        elseif (isset($condition['operator']) && isset($condition['value'])) {
                            $main_menu = $main_menu->where($key, $condition['operator'], $condition['value']);
                        }
                    } else {
                        // Default simple key-value where
                        $main_menu = $main_menu->where($key, $condition);
                    }
                }
            }

            if ($status != "all") {
                $main_menu = $main_menu->where('status', $status);
            }

            if (count($orderBy) > 0) {
                foreach ($orderBy as $orderBy_key => $orderBy_value) {
                    // dd($orderBy, $orderBy_key, $orderBy_value);
                    $main_menu = $main_menu->orderBy($orderBy_key, $orderBy_value);
                }
                $main_menu = $main_menu->orderBy('order_by', 'asc');
            } else {
                $main_menu = $main_menu->orderBy('order_by', 'asc');
            }

            if ($recordType == "all") {
                $main_menu = $main_menu->get();
            } else {
                $main_menu = $main_menu->first();
            }
            return $main_menu;
        } catch (\Throwable $th) {
            return Redirect::route('software.dashboard')->withErrors($th?->getMessage());
        }
    }

    /** Get Sub Menu */
    static function getSubMenu($wheres = [], $status = "active", $orderBy = [], $recordType = "all")
    {
        try {
            $sub_menu = SubMenu::with(['mainMenu'])->whereNotIn('name', ["Plan Master", "Admin Settings", "Manage Email"]);
            if (count($wheres) > 0) {
                foreach ($wheres as $key => $condition) {
                    // Handle different types of where conditions
                    if (is_array($condition) && isset($condition['type']) && isset($condition['values'])) {
                        // Support for whereIn
                        if ($condition['type'] == 'in' && is_array($condition['values'])) {
                            $sub_menu = $sub_menu->whereIn($key, $condition['values']);
                        }
                        // Support for custom raw where
                        elseif ($condition['type'] == 'raw') {
                            $sub_menu = $sub_menu->whereRaw($condition['query'], $condition['bindings'] ?? []);
                        }
                        // Support for other operators
                        elseif (isset($condition['operator']) && isset($condition['value'])) {
                            $sub_menu = $sub_menu->where($key, $condition['operator'], $condition['value']);
                        }
                    } else {
                        // Default simple key-value where
                        $sub_menu = $sub_menu->where($key, $condition);
                    }
                }
            }

            if ($status != "all") {
                $sub_menu = $sub_menu->where('status', $status);
            }

            if (count($orderBy) > 0) {
                foreach ($orderBy as $orderBy_key => $orderBy_value) {
                    // dd($orderBy, $orderBy_key, $orderBy_value);
                    $sub_menu = $sub_menu->orderBy($orderBy_key, $orderBy_value);
                }
                $sub_menu = $sub_menu->orderBy('order_by', 'asc');
            } else {
                $sub_menu = $sub_menu->orderBy('order_by', 'asc');
            }

            if ($recordType == "all") {
                $sub_menu = $sub_menu->get();
            } else {
                $sub_menu = $sub_menu->first();
            }
            return $sub_menu;
        } catch (\Throwable $th) {
            return Redirect::route('software.dashboard')->withErrors($th?->getMessage());
        }
    }

    static function getModuleMenu($status = "active", $wheres = [], $orderBy = [], $isDebug = false)
    {
        try {
            $team_role_menu_permission = [];

            if (Auth::guard('team_person')->check()) {
                $company = Company::where('id', Auth::guard('team_person')->user()->company_id)->first();

                $modules_sub_menu_id = $company?->panel_right;
                // return $company;

                $team_role_menu_permission = RolePermission::where('team_role_id', Auth::guard('team_person')->user()->team_role_id)
                    ->where('company_id', $company->id)
                    // ->whereIn('main_menu_id', $modules_main_menu_id)
                    ->whereIn('sub_menu_id', $modules_sub_menu_id)
                    ->where('view_flag', '1');
                if ($isDebug == 418) {
                    dd("isDebug L-418", Auth::user()->toArray(), Helper::interpolateQuery($team_role_menu_permission->toSql(), $team_role_menu_permission->getBindings()), $company->toArray(), $company?->panel_right);
                }
                $team_role_menu_permission = $team_role_menu_permission->get();

                // dd("L-338 getModuleMenu", Auth::user()->toArray(), Auth::id(), $team_role_menu_permission, $company?->panel_right);

                if (count($team_role_menu_permission) <= 0) {
                    return [];
                }
            }

            if (Auth::guard('customer')->check()) {
                $company = Company::where('id', Auth::guard('customer')->user()->company_id)->first();

                $modules_sub_menu_id = $company?->customer_panel_right;
                // return $company;

                $team_role_menu_permission = RolePermission::where('customer_role_id', Auth::guard('customer')->user()->customer_type_id)
                    ->where('company_id', $company->id)
                    // ->whereIn('main_menu_id', $modules_main_menu_id)
                    ->whereIn('sub_menu_id', $modules_sub_menu_id)
                    ->where('view_flag', '1')
                    ->get();

                if (count($team_role_menu_permission) <= 0) {
                    return [];
                }
            }

            $main_menu = MainMenu::with([
                'sub_menu' => function ($query) use ($team_role_menu_permission, $status) {
                    if (count($team_role_menu_permission) > 0) {
                        $query->whereIn('id', $team_role_menu_permission->pluck('sub_menu_id')->unique());
                    }

                    if ($status != 'all') {
                        $query->where('status', $status);
                    }
                }
            ])
                ->where(function ($query) use ($team_role_menu_permission) {
                    if (count($team_role_menu_permission) > 0) {
                        $query->whereIn('id', $team_role_menu_permission->pluck('main_menu_id')->unique());
                    }
                });
            if ($status != 'all') {
                $main_menu = $main_menu->where('status', $status);
            }

            if (count($wheres) > 0) {
                foreach ($wheres as $key => $temp_where) {
                    // dd("L-265", $key,  $temp_where);
                    $main_menu = $main_menu->where($key, $temp_where);
                }
            }

            if (count($orderBy) > 0) {
                foreach ($orderBy as $orderBy_key => $orderBy_value) {
                    // dd($orderBy, $orderBy_key, $orderBy_value);
                    $main_menu = $main_menu->orderBy($orderBy_key, $orderBy_value);
                }
                $main_menu = $main_menu->orderBy('order_by', 'asc');
            } else {
                $main_menu = $main_menu->orderBy('order_by', 'asc');
            }

            if ($isDebug == 466) {
                dd("isDebug L-466 getModuleMenu", $main_menu->get()->toArray(), $team_role_menu_permission->pluck('sub_menu_id')->unique()->join(","), $team_role_menu_permission->pluck('main_menu_id')->unique()->join(","), Helper::interpolateQuery($main_menu->toSql(), $main_menu->getBindings()), $main_menu->get()->toArray());
            }

            $main_menu = $main_menu->get();
            $tempMenu = [];
            foreach ($main_menu as $main_menu_key => $main_menu_value) {
                if ($main_menu_value->sub_menu->count() <= 0) {
                    continue;
                }
                $main_menu_routes = [];
                foreach ($main_menu_value->sub_menu as $sub_menu_key => $sub_menu_value) {
                    if (!empty($sub_menu_value->route_name) && $sub_menu_value->route_name != null) {
                        $route_name = explode('.', $sub_menu_value->route_name)[0];
                        $main_menu_routes[] = $route_name;
                    }
                }
                $main_menu_value->main_menu_routes = array_unique($main_menu_routes);
                $tempMenu[] = $main_menu_value;
                // dd($main_menu_value->sub_menu->count(), $tempMenu);
            }

            $main_menu = $tempMenu;

            if ($isDebug == 484) {
                dd("isDebug L-484 getModuleMenu", Auth::user()->toArray(), $team_role_menu_permission, $main_menu, $tempMenu);
            }

            return $main_menu;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /** Get the file upload path  */
    static function fileUploadPath($loginUser, $modulePath, $comapny_id = null)
    {
        $fileUploadPath = "/uploads";
        if ($loginUser?->company_id) {
            $company_dir = $loginUser?->company_id . ' ' . $loginUser?->company?->company_name;
            $fileUploadPath .= '/' . self::make_slug($company_dir);
        }
        if (!$loginUser?->company_id && $comapny_id) {
            $company = Company::find($comapny_id);
            $company_dir = $comapny_id . ' ' . $company?->company_name;
            $fileUploadPath .= '/' . self::make_slug($company_dir);
        }

        if ($modulePath) {
            $fileUploadPath .= '/' . $modulePath . self::trait_current_date("Y-m");
        }
        return $fileUploadPath . '/';
    }

    /** Get the Time Difference  */
    static function getTimeDifference($start, $end)
    {

        $punchIn = Carbon::parse($start);
        $punchOut = Carbon::parse($end);

        /*
        // Difference in hours and minutes
        $diffInMinutes = $punchIn->diffInMinutes($punchOut);
        $hours = floor($diffInMinutes / 60);
        $minutes = $diffInMinutes % 60;
        */

        // Get total difference in seconds
        $diffInSeconds = $punchIn->diffInSeconds($punchOut);

        // Convert to hours, minutes, seconds
        $hours = floor($diffInSeconds / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
        $seconds = $diffInSeconds % 60;

        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);

        // Combine into MySQL TIME format
        $combineTimeFormat = "{$hours}:{$minutes}:{$seconds}";

        // dd($start, $end, "Total working time: {$hours} hours and {$minutes} minutes", $diffInSeconds, $combineTimeFormat);

        return $combineTimeFormat;
    }

    /** GEt Rendered Email Funcation */
    static function getRenderedEmailTemplate($slug, $companyId, $data = [])
    {
        $template = ManageEmail::where('company_id', $companyId)
            ->where('slug', $slug)
            ->first();

        if (!$template) {
            throw new \Exception("Email template not found.");
        }

        // Compile blade content from DB
        $compiledBody = Blade::render($template->body, $data);
        $compiledSubject = Blade::render($template->subject, $data);

        return [
            'subject' => $compiledSubject,
            'body' => $compiledBody,
        ];
    }

    public static function applyDynamicMailConfig($companyId)
    {
        $settings = DB::table('mail_settings')->where('company_id', $companyId)->first();

        if (!$settings) {
            throw new \Exception('Mail settings not found for company.');
        }

        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings->host);
        Config::set('mail.mailers.smtp.port', $settings->port);
        Config::set('mail.mailers.smtp.username', $settings->username);
        Config::set('mail.mailers.smtp.password', $settings->password);
        Config::set('mail.mailers.smtp.encryption', $settings->encryption);

        Config::set('mail.from.address', $settings->from_address);
        Config::set('mail.from.name', $settings->from_name);

        // Optional: set default mailer dynamically
        Config::set('mail.default', $settings->mailer);
    }

    public static function getCompanyDetailById($company_id)
    {
        try {
            return Company::find($company_id);
        } catch (\Exception $e) {
            dd("L-373", $e->getMessage());
            //throw $th;
        }
    }

    /* sales states name by get id */

    public static function getSalesStatusNameById($company_id, $sales_slug, $sales_type)
    {
        try {
            // $normalizedSearch = strtolower(str_replace([' ', '-'], '', $target_name));

            // $salesStatuses = SalesStatus::whereRaw("LOWER(REPLACE(REPLACE(statusname, ' ', ''), '-', '')) = ?", [$normalizedSearch])
            //     ->where('company_id', $company_id)
            //     ->where('salestype', $sales_type)
            //     ->select("id")
            //     ->orderBy('id', 'desc')
            //     ->first();

            $salesStatuses = SalesStatus::where('company_id', $company_id)
                ->where('salestype', $sales_type)
                ->where('statusslug', $sales_slug)
                ->select("id")
                ->orderBy('id', 'desc')
                ->first();

            return $salesStatuses?->id ?? null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /* Convert number to word */
    public static function numberToWords($number)
    {
        $ones = array(
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen'
        );

        $tens = array(
            0 => '',
            2 => 'Twenty',
            3 => 'Thirty',
            4 => 'Forty',
            5 => 'Fifty',
            6 => 'Sixty',
            7 => 'Seventy',
            8 => 'Eighty',
            9 => 'Ninety'
        );

        if ($number == 0) {
            return 'Zero';
        }

        $words = '';

        // Handle crores (10,000,000)
        if ($number >= 10000000) {
            $crores = intval($number / 10000000);
            $words .= Helper::numberToWords($crores) . ' Crore ';
            $number %= 10000000;
        }

        // Handle lakhs (100,000)
        if ($number >= 100000) {
            $lakhs = intval($number / 100000);
            $words .= Helper::numberToWords($lakhs) . ' Lakh ';
            $number %= 100000;
        }

        // Handle thousands (1,000)
        if ($number >= 1000) {
            $thousands = intval($number / 1000);
            $words .= Helper::numberToWords($thousands) . ' Thousand ';
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $hundreds = intval($number / 100);
            $words .= $ones[$hundreds] . ' Hundred ';
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $tensDigit = intval($number / 10);
            $onesDigit = $number % 10;
            $words .= $tens[$tensDigit];
            if ($onesDigit > 0) {
                $words .= ' ' . $ones[$onesDigit];
            }
        } elseif ($number > 0) {
            $words .= $ones[$number];
        }

        return trim($words);
    }

    public static function convertToRupees($amount)
    {
        // Split amount into rupees and paise
        $parts = explode('.', number_format($amount, 2, '.', ''));
        $rupees = intval($parts[0]);
        $paise = intval($parts[1]);

        $result = '';

        if ($rupees > 0) {
            $result .= Helper::numberToWords($rupees) . ' Rupees';
        }

        if ($paise > 0) {
            if ($rupees > 0) {
                $result .= ' and ';
            }
            $result .= Helper::numberToWords($paise) . ' Paise';
        }

        if ($rupees == 0 && $paise == 0) {
            $result = 'Zero Rupees';
        }

        return $result . ' Only';
    }

    /* for send mail dynamic function */
    public static function mail_new($company_id, $type, $ntype, $user_email, $user_id, $api_name, $data, $attachments = [])
    {
        try {
            $companyData = Company::findOrFail($company_id);
            // dd('hello');

            $manageEmailData = ManageEmail::where('company_id', $company_id)
                //->where('slug', $type)
                ->where('ntype', $ntype)
                ->first();

            if (!$manageEmailData) {
                //dd("0000");
                throw new \Exception("Email template not found for company_id: $company_id, slug: $type, ntype: $ntype");
            }

            Helper::applyDynamicMailConfig($company_id);

            $template_name = $manageEmailData->name;
            $template_subject = $manageEmailData->subject;
            $template_body = $manageEmailData->body;

            // Replace placeholders in subject and body
            $replacements = [];
            // Flatten array data to allow [product_name_0], [product_name_1], etc.
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $i => $subvalue) {
                        $placeholder = "[$key" . "_$i]";
                        $boldPlaceholder = "[<strong>$key" . "_$i</strong>]";
                        $replacements[$placeholder] = $subvalue;
                        $replacements[$boldPlaceholder] = $subvalue;
                    }
                } else {
                    $replacements["[$key]"] = $value;
                    $replacements["[<strong>$key</strong>]"] = $value;
                }
            }

            $template_subject = strtr($template_subject, $replacements);
            $template_body = strtr($template_body, $replacements);

            // $settings = DB::table('mail_settings')->where('company_id', $company_id)->first();
            // if (!$settings) {
            //     throw new \Exception('Mail settings not found for company.');
            // }
            // $bcc = $settings->bcc ?? '';

            //dd($user_email,$template_subject,$template_body,$attachments);
            Log::info(" GenericMail constructor called");
            $response = Mail::to($user_email)->send(new GenericMail($template_subject, $template_body, $attachments));
            // $email_log_data = [
            //     'company_id' => $company_id,
            //     'user_id' => $user_id,
            //     'user_type' => 'team',
            //     'user_email' => $user_email,
            //     'org_subject' => $manageEmailData->subject,
            //     'org_body_template' => $manageEmailData->body,
            //     'subject' => $template_subject,
            //     'body_template' => $template_body,
            //     'send_status' => 'send',
            //     'send_date' => date('d-m-Y h:i A'),
            //     'created_at' => date('d-m-Y h:i A'),
            //     'api_name' => $api_name,
            // ];
            //dd($email_log_data);
            // EmailLog::create($email_log_data);
            // dd($response);

        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    public static function getProductStockTotalQuantity($company_id, $warehouse_id, $product_category_id, $product_variant_price_id, $is_combo = false)
    {
        try {
            if ($is_combo) {
                $combo = ProductCombo::with('combo_items.product')->find($product_variant_price_id);
                if (!$combo) {
                    return 0;
                }

                $min_combo_qty = PHP_INT_MAX;
                foreach ($combo->combo_items as $item) {
                    if ($item->order_type == 'kg') {
                        $stock = self::getProductStockTotalKG($company_id, $warehouse_id, $item->product->product_category_id, $item->product_variant_price_id);
                    } else {
                        $stock = self::getProductStockTotalQuantity($company_id, $warehouse_id, $item->product->product_category_id, $item->product_variant_price_id);
                    }

                    // Calculate how many combos can be made with this component
                    $required_qty = (float)($item->quantity ?? 1);
                    $possible = ($required_qty > 0) ? floor((float)$stock / $required_qty) : 0;

                    if ($possible < $min_combo_qty) {
                        $min_combo_qty = (int)$possible;
                    }
                }
                return ($min_combo_qty === PHP_INT_MAX) ? 0 : $min_combo_qty;
            }

            $total_quantity = ProductStockHistory::where('company_id', $company_id)
                ->where('warehouse_id', $warehouse_id)
                ->where('product_category_id', $product_category_id)
                ->where('product_variant_price_id', $product_variant_price_id)
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->value('total_quantity');
            return $total_quantity;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public static function getProductStockTotalKG($company_id, $warehouse_id, $product_category_id, $product_variant_price_id, $is_combo = false)
    {
        try {
            if ($is_combo) {
                $combo = ProductCombo::find($product_variant_price_id);
                if (!$combo) {
                    return 0;
                }
                // For KG, we calculate based on the minimum possible COMBO sets
                $total_qty = self::getProductStockTotalQuantity($company_id, $warehouse_id, $product_category_id, $product_variant_price_id, true);
                return (float) $total_qty * (float) ($combo->net_wt ?? 0);
            }

            $total_kg = ProductStockHistory::where('company_id', $company_id)
                ->where('warehouse_id', $warehouse_id)
                ->where('product_category_id', $product_category_id)
                ->where('product_variant_price_id', $product_variant_price_id)
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->value('total_kg');

            return $total_kg;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public static function qtyToKgConvertForStock($qty, $productWeightGram)
    {
        try {
            if (!$productWeightGram || $qty <= 0) {
                return 0;
            }
            // qty → kg
            $kg = ($qty * $productWeightGram) / 1000;
            return self::roundKgSmart($kg);
        } catch (\Throwable $e) {
            Log::error('qtyToKg Conversion Error: ' . $e->getMessage(), [
                'pcs' => $qty,
                'grams' => $productWeightGram
            ]);
            return 0;
        }
    }

    public static function kgToQtyConvertForStock($kg, $productWeightGram)
    {
        try {
            if (!$productWeightGram || $kg <= 0) {
                return 0;
            }
            // kg → qty
            $qty = ($kg * 1000) / $productWeightGram;

            return self::roundKgSmart($qty);
        } catch (\Throwable $e) {
            Log::error('kgToQty Conversion Error: ' . $e->getMessage(), [
                'kg' => $kg,
                'grams' => $productWeightGram
            ]);
            return 0;
        }
    }

    public static function roundKgSmart($value)
    {
        $rounded = round($value, 4);
        if (abs($rounded - round($rounded)) <= 0.005) {
            return round($rounded); // return integer
        }

        return round($value, 3);
    }

    static function checkPermissionModule($company_id, $menu_name)
    {
        try {
            $sub_menu = SubMenu::with(['mainMenu'])->where('name', $menu_name)->where("platform", 'panel')->where("status", "active")->first();
            if ($sub_menu) {
                $main_menu_id = $sub_menu->main_menu_id;
                $sub_menu_id = $sub_menu->id;
                $checkPermission = RolePermission::where('company_id', $company_id)
                    ->where('main_menu_id', $main_menu_id)
                    ->where('sub_menu_id', $sub_menu_id)
                    ->where('status', 'active')
                    ->first();

                return $checkPermission;
            }
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /** Application Permission */
    static function checkAppPermission($platform = 'panel', $table_name, $company_id, $team_id, $menu_name, $field = null)
    {
        // dd("L-782",$table_name, $company_id, $team_id, $menu_name);
        try {
            $sub_menu = SubMenu::with(['mainMenu'])->where('name', $menu_name)->where("platform", $platform)->where("status", "active")->first();
            if ($sub_menu) {
                $main_menu_id = $sub_menu->main_menu_id;
                $sub_menu_id = $sub_menu->id;
                $checkAppPermission = RolePermission::query();
                if ($table_name == 'role_permissions_app') {
                    $checkAppPermission = RolePermissionsApp::query();
                } else {
                    return null;
                }
                $checkAppPermission = $checkAppPermission->where('company_id', $company_id);
                $checkAppPermission = $checkAppPermission->where('main_menu_id', $main_menu_id);
                $checkAppPermission = $checkAppPermission->where('sub_menu_id', $sub_menu_id);
                $checkAppPermission = $checkAppPermission->where('team_id', $team_id);
                $checkAppPermission = $checkAppPermission->where('status', 'active');
                if ($field) {
                    $checkAppPermission = $checkAppPermission->where($field, 1);
                    $checkAppPermission = $checkAppPermission->first();
                    return $checkAppPermission ? true : false;
                }
                // dd("L-798", self::interpolateQuery($checkAppPermission?->toSql(), $checkAppPermission?->getBindings()), $table_name, $company_id, $team_id, $menu_name);
                $checkAppPermission = $checkAppPermission->first();

                dd("L-802", $table_name, $company_id, $team_id, $menu_name, $field, $checkAppPermission);
                return $checkAppPermission;
            }
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public static function sendPushNotification(array $data)
    {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['body'])) {
                Log::warning('Push Notification skipped: Title or Body missing.', $data);
                return;
            }


            if ($data['user_type'] === 'Team') {
                $mainNotificationAddBtn = self::checkPermissionModule($data['company_id'], 'Notification');
                if (!$mainNotificationAddBtn->add_flag || !$mainNotificationAddBtn->view_flag) {
                    return; // Permission not found for team, so stop here
                }
            }


            // Create DB notification entry
            $notification = Notification::create([
                'user_id' => $data['user_id'] ?? null,
                'user_type' => $data['user_type'] ?? null,
                'title' => $data['title'],
                'body' => $data['body'],
                'module_name' => $data['module_name'] ?? null,
                'module_id' => $data['module_id'] ?? null,
                'module_action' => $data['module_action'] ?? null,
                'notify_read' => 0,
                'created_by' => $data['created_by'] ?? null,
                'created_type' => $data['created_type'] ?? null,
                'status' => $data['status'] ?? null,
                'send_status' => 'pending',
            ]);

            // Check device token and send push notification
            $token = null;

            if ($data['user_type'] == 'Team') {
                $user = TeamPerson::find($data['user_id'] ?? null);
            } else {
                $user = AdminSoftware::find($data['user_id'] ?? null);
            }

            if ($user && !empty($user->device_token)) {
                $token = $user->device_token;
            }

            if (!$token) {
                Log::info("No device token found. Notification not sent.", $data);
                return;
            }

            $firebase = app(FirebaseService::class);
            $results = $firebase->sendNotification([$token], $data['title'], $data['body']);

            // Check Firebase response and update DB status
            foreach ($results as $result) {
                if ($result['token'] === $token) {
                    $notification->send_status = $result['status'] === 'success' ? 'success' : 'failed';
                    $notification->save();
                }
            }
        } catch (\Throwable $e) {
            Log::error('Push Notification error: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }


    public static function sendPushNotificationForPacking(array $data)
    {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['body'])) {
                Log::warning('Push Notification skipped: Title or Body missing.', $data);
                return;
            }

            // Create DB notification entry
            $notification = Notification::create([
                'user_id' => $data['user_id'] ?? null,
                'user_type' => $data['user_type'] ?? null,
                'title' => $data['title'],
                'body' => $data['body'],
                'module_name' => $data['module_name'] ?? null,
                'module_id' => $data['module_id'] ?? null,
                'module_action' => $data['module_action'] ?? null,
                'notify_read' => 0,
                'created_by' => $data['created_by'] ?? null,
                'created_type' => $data['created_type'] ?? null,
                'status' => $data['status'] ?? null,
                'send_status' => 'pending',
            ]);

            // Check device token and send push notification
            $token = null;

            if ($data['user_type'] == 'Team') {
                $user = TeamPerson::find($data['user_id'] ?? null);
            } else {
                $user = AdminSoftware::find($data['user_id'] ?? null);
            }

            if ($user && !empty($user->device_token)) {
                $token = $user->device_token;
            }

            if (!$token) {
                Log::info("No device token found. Notification not sent.", $data);
                return;
            }

            $firebase = app(FirebaseService::class);
            // $results = $firebase->sendNotification([$token], $data['title'], $data['body']);

            // Add action buttons to notification data
            // $notificationData = [
            //     'title' => $data['title'],
            //     'body' => $data['body'],
            //     'notification_id' => $notification->id,
            //     'module_id' => $data['module_id'] ?? null,
            //     'module_name' => $data['module_name'] ?? null,
            // ];

            // $results = $firebase->sendNotificationWithActions(
            //     [$token],
            //     $data['title'],
            //     $data['body'],
            //     $notificationData
            // );

            $noti_data = [
                'notification_id' => (string) $notification->id,
                'module_id' => $data['module_id'] ?? null,
                'module_name' => $data['module_name'] ?? null,
                'test' => 'true',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ];

            $results = $firebase->sendNotificationWithActions(
                [$user->device_token],
                $data['title'],
                $data['body'],
                $noti_data
            );

            // Check Firebase response and update DB status
            foreach ($results as $result) {
                if ($result['token'] === $token) {
                    $notification->send_status = $result['status'] === 'success' ? 'success' : 'failed';
                    $notification->save();
                }
            }
        } catch (\Throwable $e) {
            Log::error('Push Notification error: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }


    public static function sendPackingSlipReminderNotifications($packingSlipId, $companyId, $creatorId, $creatorType, $packingSlipNo)
    {
        try {

            $delayCounter = 0; // 0 થી શરૂ કરો testing માટે
            $scheduledCount = 0;

            // Get team members (excluding creator)
            $teamMembers = TeamPerson::where('company_id', $companyId)
                ->where('status', 'active')
                ->when($creatorType == 'team', function ($query) use ($creatorId) {
                    $query->where('id', '!=', $creatorId);
                })
                ->get();

            if ($teamMembers->isEmpty()) {
                return;
            }

            foreach ($teamMembers as $index => $member) {
                // Permission check કરો
                try {
                    $hasPermission = self::checkPermissionModule($companyId, 'Packing Slip');
                    if (!$hasPermission || !isset($hasPermission->view_flag) || !$hasPermission->view_flag) {
                        continue;
                    }
                } catch (\Exception $permError) {
                    continue;
                }

                // Notification data prepare કરો
                $notificationData = [
                    'user_id' => $member->id,
                    'user_type' => 'Team',
                    'module_name' => 'Packing Slip',
                    'module_id' => $packingSlipId,
                    'module_action' => 'created',
                    'title' => 'New Packing Slip Created',
                    'body' => "Packing Slip #{$packingSlipNo} has been created. Please review.",
                    'status' => 'info',
                    'created_by' => $creatorId,
                    'created_type' => $creatorType,
                    'company_id' => $companyId
                ];

                try {
                    // Job dispatch કરો
                    $job = \App\Jobs\SendPackingSlipNotification::dispatch($notificationData)
                        ->delay(now()->addMinutes($delayCounter));

                    $delayCounter++;
                    $scheduledCount++;
                } catch (\Exception $dispatchError) {
                    Log::error('❌ Failed to dispatch job', [
                        'member_id' => $member->id,
                        'error' => $dispatchError->getMessage(),
                        'trace' => $dispatchError->getTraceAsString()
                    ]);
                }
            }

            return [
                'success' => true,
                'scheduled_count' => $scheduledCount
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public static function webpToBase64($imagePath)
    {
        try {
            if (empty($imagePath)) {
                return '';
            }

            // If path starts with '/', make it absolute to public/
            if (!str_starts_with($imagePath, public_path())) {
                $fullPath = public_path(ltrim($imagePath, '/'));
            } else {
                $fullPath = $imagePath;
            }

            // Check if file exists and is not a directory
            if (!file_exists($fullPath) || is_dir($fullPath)) {
                return '';
            }

            $mime = mime_content_type($fullPath);
            $data = file_get_contents($fullPath);
            // Encode to base64
            $base64 = 'data:' . $mime . ';base64,' . base64_encode($data);
            return $base64;
        } catch (\Exception $e) {
            Log::error("Image to Base64 failed for {$imagePath}: " . $e->getMessage());
            return '';
        }
    }

    public static function storeAccountLedger(array $data)
    {
        try {

            $company = Company::select('id', 'company_name', 'email', 'account_effect_by')->findOrFail($data['company_id']);
            $accountEffect = $company->account_effect_by;
            if ($accountEffect == $data['reference_type']) {
                $entryDate = Carbon::now()->format('Y-m-d');

                $debit = $data['payment_mode'] === 'debit' ? $data['amount'] : 0;
                $credit = $data['payment_mode'] === 'credit' ? $data['amount'] : 0;

                $runningBalance = $data['running_balance'] ?? 0;
                $runningBalance += ($debit - $credit);

                $matchConditions = [
                    'company_id' => $data['company_id'],
                ];

                $description = 'Payment via ' . ucfirst($data['payment_mode']) . '. Receipt No: ' . ($data['receipt_no'] ?? 'N/A') . '.';

                if (!empty($data['reference_type'])) {
                    if ($data['reference_type'] === 'quotation') {
                        $matchConditions['quotation_id'] = $data['quotation_id'] ?? null;
                        $description = 'Ledger entry created for Quotation No. <strong>' . ($data['quotation_no'] ?? 'N/A') .
                            ' </strong> approved on <strong>' . $entryDate . '</strong>';
                    } elseif ($data['reference_type'] === 'order') {
                        $matchConditions['sales_order_id'] = $data['sales_order_id'] ?? null;
                        $description = 'Ledger entry created for Sales Order No <strong>' . ($data['sales_order_no'] ?? 'N/A') .
                            ' </strong> approved on <strong>' . $entryDate . '</strong>';
                    }
                }
                AccountLedger::where($matchConditions)->whereNull('receipt_id')->delete();

                $ledgerEntry = AccountLedger::create([
                    'company_id' => $data['company_id'],
                    'customer_type_id' => $data['customer_type_id'],
                    'customer_id' => $data['customer_id'],
                    'entry_date' => $entryDate,
                    'reference_type' => $data['reference_type'] ?? null,
                    'quotation_id' => $data['quotation_id'] ?? null,
                    'sales_order_id' => $data['sales_order_id'] ?? null,
                    'payment_mode' => $data['payment_mode'] ?? null,
                    'amount' => $data['amount'],
                    'debit_amount' => $debit,
                    'credit_amount' => $credit,
                    'description' => $description,
                    'closing_balance' => $runningBalance,
                ]);

                return $ledgerEntry;
            }
        } catch (\Exception $e) {
            Log::error('Failed to store account ledger entry', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }


    public static function getTransportDetailsByCustomer($customer_id)
    {
        try {

            $invoice = Invoice::where('customer_id', $customer_id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$invoice) {
                return [
                    'transport_by_id' => null,
                    'transport_details_id' => null,
                ];
            }

            return [
                'transport_by_id' => $invoice->transport_by_id,
                'transport_details_id' => $invoice->transport_details_id,
            ];
        } catch (\Exception $e) {
            return [
                'transport_by_id' => null,
                'transport_details_id' => null,
            ];
        }
    }

    public static function formatWeight($val)
    {
        if ($val === null || $val === "" || $val === ".") {
            return $val;
        }

        $num = floatval($val);
        if (!is_numeric($num)) {
            return "";
        }

        $intPart = floor($num);
        $decimal = round(($num - $intPart) * 1000);
        $decStr = str_pad($decimal, 3, "0", STR_PAD_LEFT);

        $firstDigit = intval($decStr[0]);
        $lastTwo = intval(substr($decStr, 1));

        $newFirstDigit = $firstDigit;
        $finalDecimal = 0;

        if ($lastTwo <= 29) {
            $finalDecimal = $newFirstDigit * 100;
        } elseif ($lastTwo <= 79) {
            $finalDecimal = $newFirstDigit * 100 + 50;
        } else {
            $newFirstDigit = $firstDigit + 1;
            $finalDecimal = $newFirstDigit * 100;
        }

        $result = $intPart + ($finalDecimal / 1000);
        return number_format($result, 3, '.', '');
    }

    public static function generateJobWorkIssuePdf($id)
    {
        try {
            Log::info("Helper::generateJobWorkIssuePdf started for ID: {$id}");

            $jobIssue = JobWorkIssue::with([
                'company',
                'vendor',
                'job_work_issue_items',
                'team_person',
                'admin_person',
                'issueForProcess',
                'job_work_type'
            ])->withTrashed()->find($id);

            if (!$jobIssue) {
                Log::warning("Helper::generateJobWorkIssuePdf: Job Work Issue not found for ID: {$id}");
                return false;
            }

            $modules = [
                'folder_path' => 'software.modules.job_work_management.job_work_issue',
            ];

            $sidebarMainLogo = public_path('software/img/logo.png');
            $sidebarSmallLogo = public_path('software/img/ring.png');
            $sidebarHeaderLogo = '';

            if ($jobIssue->company_id) {
                $company = self::getCompanyDetailById($jobIssue->company_id);
                if ($company && $company?->id) {
                    $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                    $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                    $sidebarHeaderLogo = $company?->order_header_logo ?? '';
                }
            }

            $sidebarMainLogo = self::webpToBase64($sidebarMainLogo);
            $sidebarHeaderLogo = self::webpToBase64($sidebarHeaderLogo);

            $company_id = $jobIssue->company_id;
            $viewPath = $modules['folder_path'] . '.' . ($jobIssue?->company?->jobissue_print_format ?? 'single_pdf');

            $html = View::make($viewPath, compact('jobIssue', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

            $company_slug = Str::slug($jobIssue->company->company_name ?? 'company');
            $folder = "uploads/{$company_id}-{$company_slug}/jobissues_pdf/";
            $uploadedPath = public_path($folder);

            if (!File::exists($uploadedPath)) {
                // dd("hiii",$uploadedPath);
                File::makeDirectory($uploadedPath, 0777, true);
            }

            // Delete old PDFs for this specific job issue ID
            $oldFiles = File::glob($uploadedPath . "JobWorkIssue_*_" . $jobIssue->id . ".pdf");
            foreach ($oldFiles as $oldFile) {
                File::delete($oldFile);
            }

            $safeIssueNo = str_replace(['/', '\\'], '-', $jobIssue->job_work_issue_no);
            $filename = 'JobWorkIssue_' . $safeIssueNo . '_' . $jobIssue->id . '.pdf';
            $filepath = $uploadedPath . $filename;

            Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->save($filepath);

            Log::info("Helper::generateJobWorkIssuePdf finished for ID: {$id}. Saved to: {$filepath}");
            return true;
        } catch (\Exception $e) {
            Log::error("Helper::generateJobWorkIssuePdf failed for ID: {$id}. Error: " . $e->getMessage());
            return false;
        }
    }
    public static function generateJobWorkReceivePdf($id)
    {
        // dd("L-1421");
        try {
            Log::info("Helper::generateJobWorkReceivePdf started for ID: {$id}");

            $jobReceive = JobWorkReceive::with([
                'company',
                'vendor',
                'job_work_receive_items',
                'team_person',
                'admin_person',
                'issueForProcess',
                'job_work_type'
            ])->withTrashed()->find($id);

            if (!$jobReceive) {
                Log::warning("Helper::generateJobWorkReceivePdf: Job Work Issue not found for ID: {$id}");
                return false;
            }

            $modules = [
                'folder_path' => 'software.modules.job_work_management.job_work_receive',
            ];

            $sidebarMainLogo = public_path('software/img/logo.png');
            $sidebarSmallLogo = public_path('software/img/ring.png');
            $sidebarHeaderLogo = '';

            if ($jobReceive->company_id) {
                $company = self::getCompanyDetailById($jobReceive->company_id);
                if ($company && $company?->id) {
                    $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                    $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                    $sidebarHeaderLogo = $company?->order_header_logo ?? '';
                }
            }

            $sidebarMainLogo = self::webpToBase64($sidebarMainLogo);
            $sidebarHeaderLogo = self::webpToBase64($sidebarHeaderLogo);

            $company_id = $jobReceive->company_id;
            $viewPath = $modules['folder_path'] . '.' . ($jobReceive?->company?->jobreceive_print_format ?? 'single_pdf');

            $html = View::make($viewPath, compact('jobReceive', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

            $company_slug = Str::slug($jobReceive->company->company_name ?? 'company');
            $folder = "uploads/{$company_id}-{$company_slug}/jobReceives_pdf/";
            $uploadedPath = public_path($folder);
            // dd($uploadedPath);
            if (!File::exists($uploadedPath)) {
                //    dd("hiiii",$uploadedPath);
                File::makeDirectory($uploadedPath, 0777, true);
            }

            // Delete old PDFs for this specific job issue ID
            $oldFiles = File::glob($uploadedPath . "JobWorkReceive_*_" . $jobReceive->id . ".pdf");
            foreach ($oldFiles as $oldFile) {
                File::delete($oldFile);
            }

            $safeReceiveNo = str_replace(['/', '\\'], '-', $jobReceive->job_work_receive_no);
            $filename = 'JobWorkReceive_' . $safeReceiveNo . '_' . $jobReceive->id . '.pdf';
            $filepath = $uploadedPath . $filename;

            Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->save($filepath);

            Log::info("Helper::generateJobWorkReceivePdf finished for ID: {$id}. Saved to: {$filepath}");
            return true;
        } catch (\Exception $e) {
            Log::error("Helper::generateJobWorkReceivePdf failed for ID: {$id}. Error: " . $e->getMessage());
            return false;
        }
    }
    public static function generateInvoicePdf($id)
    {
        try {
            Log::info("Helper::generateInvoicePdf started for ID: {$id}");

            $Invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'sales_order'])->findOrFail($id);

            if (!$Invoice) {
                Log::warning("Helper::generateInvoicePdf: Invoice not found for ID: {$id}");
                return false;
            }

            $modules = [
                'folder_path' => 'software.modules.sales_management.invoice',
            ];

            $sidebarMainLogo = public_path('software/img/logo.png');
            $sidebarSmallLogo = public_path('software/img/ring.png');
            $sidebarHeaderLogo = '';

            if ($Invoice->company_id) {
                $company = self::getCompanyDetailById($Invoice->company_id);
                if ($company && $company?->id) {
                    $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                    $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                    $sidebarHeaderLogo = $company?->order_header_logo ?? '';
                }
            }

            $sidebarMainLogo = self::webpToBase64($sidebarMainLogo);
            $sidebarHeaderLogo = self::webpToBase64($sidebarHeaderLogo);

            $company_id = $Invoice->company_id;
            $viewPath = $modules['folder_path'] . '.default_single_pdf';

            $html = View::make($viewPath, compact('Invoice', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

            $year = $Invoice->created_at->format('Y');
            $month = $Invoice->created_at->format('m');
            $company_slug = Str::slug($Invoice->company->company_name ?? 'company');
            $folder = "uploads/{$company_id}-{$company_slug}/Invoice_pdf/";
            $uploadedPath = public_path($folder);

            if (!File::exists($uploadedPath)) {
                File::makeDirectory($uploadedPath, 0777, true);
            }

            // Delete old PDFs for this specific invoice ID
            $oldFiles = File::glob($uploadedPath . "Invoice_*_" . $Invoice->id . ".pdf");
            foreach ($oldFiles as $oldFile) {
                File::delete($oldFile);
            }

            $safeInvoiceNo = str_replace(['/', '\\'], '-', $Invoice->invoice_order_no);
            $filename = 'Invoice_' . $safeInvoiceNo . '_' . $Invoice->id . '.pdf';
            $filepath = $uploadedPath . $filename;

            Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->save($filepath);

            Log::info("Helper::generateInvoicePdf finished for ID: {$id}. Saved to: {$filepath}");
            return true;
        } catch (\Exception $e) {
            Log::error("Helper::generateInvoicePdf failed for ID: {$id}. Error: " . $e->getMessage());
            return false;
        }
    }

    public static function getRateSheetPath($companyId = null)
    {
        try {
            // 1. If company ID not specified, check active login/session
            if (!$companyId) {
                if (auth()->guard('team_person')->check()) {
                    $companyId = auth()->guard('team_person')->user()->company_id ?? null;
                } elseif (auth()->check()) {
                    $companyId = auth()->user()->company_id ?? session('company_id') ?? null;
                }
            }

            // 2. Check company by ID
            if ($companyId) {
                $company = \App\Models\Company::find($companyId);
                if ($company && !empty($company->rate_sheet) && file_exists(public_path($company->rate_sheet))) {
                    return public_path($company->rate_sheet);
                }
            }

            // 3. Fallback: check any company that has an uploaded rate_sheet in database
            $anyCompany = \App\Models\Company::whereNotNull('rate_sheet')->where('rate_sheet', '!=', '')->orderBy('id', 'desc')->first();
            if ($anyCompany && !empty($anyCompany->rate_sheet) && file_exists(public_path($anyCompany->rate_sheet))) {
                return public_path($anyCompany->rate_sheet);
            }
        } catch (\Throwable $th) {}

        // 4. Look in public/uploads/rate_sheet/ for the latest uploaded excel file dynamically
        $folder = public_path('uploads/rate_sheet');
        if (is_dir($folder)) {
            $files = glob($folder . '/*.{xlsx,xls,XLSX,XLS}', GLOB_BRACE);
            if (!empty($files)) {
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                return $files[0];
            }
        }

        // 5. Look in storage/app/rate_sheet/
        $storageFolder = storage_path('app/rate_sheet');
        if (is_dir($storageFolder)) {
            $files = glob($storageFolder . '/*.{xlsx,xls,XLSX,XLS}', GLOB_BRACE);
            if (!empty($files)) {
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                return $files[0];
            }
        }

        return '';
    }

    public static function getRateSheetMap()
    {
        return cache()->remember('sipl_rate_sheet_map', 3600, function () {
            $filePath = self::getRateSheetPath();
            if (empty($filePath) || !file_exists($filePath)) {
                return [];
            }

            $map = [];
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                $sheetNames = $spreadsheet->getSheetNames();
                foreach ($sheetNames as $sheetName) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    if (!$sheet) continue;
                    $rows = $sheet->toArray();
                    for ($i = 2; $i < count($rows); $i++) {
                        $code = trim((string)($rows[$i][1] ?? ''));
                        $name = trim((string)($rows[$i][2] ?? ''));
                        if (empty($code) && empty($name)) continue;

                        $jobWorkRate = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][6] ?? 0)));
                        $scrapRate = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][3] ?? 0)));
                        $grossWeight = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][4] ?? 0)));
                        $grossLabourCharges = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][7] ?? 0)));
                        $kgInGross = $grossWeight > 0 ? (1000 / $grossWeight) : 0;
                        if ($grossLabourCharges <= 0 && $kgInGross > 0 && $jobWorkRate > 0) {
                            $grossLabourCharges = round($kgInGross * $jobWorkRate, 2);
                        }
                        $jobWorkPcsRate = $jobWorkRate > 0 ? round($jobWorkRate / 144, 2) : 0;
                        $completeCostKg = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][11] ?? 0)));
                        $grossRate = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][12] ?? 0)));
                        $pcsRate = floatval(preg_replace('/[^0-9.]/', '', (string)($rows[$i][13] ?? 0)));

                        $details = [
                            'sheet_name' => $sheetName,
                            'item_code' => $code,
                            'item_name' => $name,
                            'scrap_rate' => $scrapRate,
                            'gross_weight' => $grossWeight,
                            'job_work_gross_rate' => $jobWorkRate,
                            'gross_labour_charges' => $grossLabourCharges,
                            'job_work_labour_rate_kg' => $grossLabourCharges,
                            'job_work_pcs_rate' => $jobWorkPcsRate,
                            'complete_cost_kg' => $completeCostKg,
                            'gross_rate' => $grossRate,
                            'pcs_rate' => $pcsRate,
                        ];

                        if (!empty($code)) {
                            $normCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $code));
                            $map['code_' . $normCode] = $details;
                        }
                        if (!empty($name)) {
                            $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
                            $map['name_' . $normName] = $details;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Rate Sheet Helper error: ' . $e->getMessage());
            }

            return $map;
        });
    }

    public static function getRateSheetProductDetails($partNo = null, $productName = null)
    {
        $map = self::getRateSheetMap();
        if (!empty($partNo)) {
            $normCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $partNo));
            if (isset($map['code_' . $normCode])) {
                return $map['code_' . $normCode];
            }
        }
        if (!empty($productName)) {
            $normName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $productName));
            if (isset($map['name_' . $normName])) {
                return $map['name_' . $normName];
            }
        }
        return null;
    }

    /**
     * Get or refresh Liliya WhatsApp API Session Token
     *
     * @param string|null $email
     * @param string|null $password
     * @param bool $forceRefresh
     * @return array ['token' => string|null, 'client_id' => string, 'error' => string|null]
     */
    public static function getLiliyaSessionToken($email = null, $password = null, $forceRefresh = false, $clientId = null)
    {
        $email = $email ?: config('services.liliya.email', env('LILIYA_EMAIL', ''));
        $password = $password ?: config('services.liliya.password', env('LILIYA_PASSWORD', ''));
        $clientId = $clientId ?: config('services.liliya.client_id', env('LILIYA_CLIENT_ID', 'SHALIB467'));
        $cacheKey = 'liliya_session_token_' . md5(($email ?: 'default') . '_' . ($clientId ?: 'default'));

        if (!$forceRefresh && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if (!empty($cached['token'])) {
                return $cached;
            }
        }

        if (empty($email) || empty($password)) {
            return [
                'token' => null,
                'client_id' => $clientId,
                'error' => 'Liliya email or password not configured in .env'
            ];
        }

        $ch = curl_init('https://api.liliya.io/auth/v1/sign-in');
        $postData = [
            'user_email' => $email,
            'user_password' => $password,
            'fcm_token' => '',
            'shop' => ''
        ];

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            \Illuminate\Support\Facades\Log::error("Liliya Auth cURL Error: {$curlError}");
            return ['token' => null, 'client_id' => $clientId, 'error' => "cURL error: {$curlError}"];
        }

        $resData = json_decode($response, true);
        if (isset($resData['success']) && (int)$resData['success'] === 1 && !empty($resData['session_token'])) {
            $token = $resData['session_token'];
            if (!empty($resData['credential_list'][0]['wp_cr_client_id'])) {
                $clientId = $resData['credential_list'][0]['wp_cr_client_id'];
            }

            $cacheData = [
                'token' => $token,
                'client_id' => $clientId,
                'error' => null
            ];

            // Cache token for 23 hours (82800 seconds)
            \Illuminate\Support\Facades\Cache::put($cacheKey, $cacheData, 82800);
            return $cacheData;
        }

        $errMsg = $resData['message'] ?? 'Authentication failed with Liliya WhatsApp API.';
        \Illuminate\Support\Facades\Log::error("Liliya Auth Failed: {$errMsg} | HTTP: {$httpCode}");
        return ['token' => null, 'client_id' => $clientId, 'error' => $errMsg];
    }

    /**
     * Send WhatsApp Auto Reply for Lead Inquiry using Marketing Liliya Account
     *
     * @param \App\Models\Inquiry $inquiry
     * @return array
     */
    public static function sendInquiryAutoReply($inquiry)
    {
        try {
            if (!$inquiry) {
                return ['success' => false, 'message' => 'Inquiry not provided'];
            }

            $phone = $inquiry->whatsapp_no ?: $inquiry->mobile_no;
            if (empty($phone)) {
                return ['success' => false, 'message' => 'No phone number available'];
            }

            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) === 10) {
                $cleanPhone = '91' . $cleanPhone;
            }

            $customerName = $inquiry->contact_person_name ?: ($inquiry->customer_name ?: 'Valued Customer');
            $platform = $inquiry->platform ?: 'Online Portal';

            // Find requirement / product name
            $productName = '';
            if (method_exists($inquiry, 'inquiry_products')) {
                $firstInqProduct = $inquiry->inquiry_products()->with('product')->first();
                $productName = $firstInqProduct?->product?->product_name ?? '';
            }
            if (empty($productName) && !empty($inquiry->requirement_details)) {
                $lines = explode("\n", strip_tags($inquiry->requirement_details));
                $productName = trim($lines[0] ?? '');
            }
            if (empty($productName)) {
                $productName = 'Industrial Products';
            }

            if (strlen($productName) > 60) {
                $productName = substr($productName, 0, 57) . '...';
            }

            $email = env('LILIYA_MARKETING_EMAIL', 'ocean2@shalibadra.com');
            $password = env('LILIYA_MARKETING_PASSWORD', 'Ocean2@123');
            $clientId = env('LILIYA_MARKETING_CLIENT_ID', 'dhruvi192');
            $templateName = env('LILIYA_AUTOREPLY_TEMPLATE', 'shalibhadra_inquiry_autoreply_v');

            $auth = self::getLiliyaSessionToken($email, $password);
            if (empty($auth['token'])) {
                \Illuminate\Support\Facades\Log::error("Liliya AutoReply Auth Error: " . ($auth['error'] ?? 'Unknown'));
                return ['success' => false, 'message' => $auth['error'] ?? 'Auth failed'];
            }

            $sessionToken = $auth['token'];
            $activeClientId = $clientId ?: ($auth['client_id'] ?: 'dhruvi192');

            $parameters = [$customerName, $platform, $productName];

            // Dynamic Brochure from Company Details
            $filePath = null;
            if (!empty($inquiry->company_id)) {
                $company = \App\Models\Company::find($inquiry->company_id);
                if ($company && !empty($company->brochure) && file_exists(public_path($company->brochure))) {
                    $filePath = public_path($company->brochure);
                }
            }

            // Fallback to default brochure if company hasn't uploaded a specific one
            if (!$filePath) {
                $defaultBrochure = public_path("uploads/brochure/Shalibhadra_Brochure.pdf");
                if (file_exists($defaultBrochure)) {
                    $filePath = $defaultBrochure;
                }
            }

            $docDisplayName = 'Shalibhadra Brochure.pdf';

            $result = self::executeLiliyaSendRequest($cleanPhone, $templateName, $parameters, $filePath, $activeClientId, $sessionToken, $docDisplayName);

            if (isset($result['http_code']) && $result['http_code'] == 401) {
                $auth = self::getLiliyaSessionToken($email, $password, true);
                if (!empty($auth['token'])) {
                    $result = self::executeLiliyaSendRequest($cleanPhone, $templateName, $parameters, $filePath, $activeClientId, $auth['token'], $docDisplayName);
                }
            }

            \Illuminate\Support\Facades\Log::info("Inquiry Auto-Reply sent for Inquiry #{$inquiry->inquiry_no} to {$cleanPhone}: " . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Inquiry AutoReply Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp General/Discussion message for an Inquiry using Marketing Liliya Account
     *
     * @param \App\Models\Inquiry $inquiry
     * @param string|int|null $userId
     * @param string $createdType
     * @return array
     */
    public static function sendInquiryGeneralWhatsApp($inquiry, $userId = null, $createdType = 'Team')
    {
        try {
            if (!$inquiry) {
                return ['success' => false, 'message' => 'Inquiry not provided'];
            }

            $phone = $inquiry->whatsapp_no ?: $inquiry->mobile_no;
            if (empty($phone)) {
                return ['success' => false, 'message' => 'No phone number available'];
            }

            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) === 10) {
                $cleanPhone = '91' . $cleanPhone;
            }

            $customerName = $inquiry->contact_person_name ?: ($inquiry->customer_name ?: 'Valued Customer');

            // Find requirement / product name
            $productName = '';
            if (method_exists($inquiry, 'inquiry_products')) {
                $firstInqProduct = $inquiry->inquiry_products()->with('product')->first();
                $productName = $firstInqProduct?->product?->product_name ?? '';
            }
            if (empty($productName) && !empty($inquiry->requirement_details)) {
                $lines = explode("\n", strip_tags($inquiry->requirement_details));
                $productName = trim($lines[0] ?? '');
            }
            if (empty($productName)) {
                $productName = 'Industrial Products';
            }

            if (strlen($productName) > 60) {
                $productName = substr($productName, 0, 57) . '...';
            }

            $email = env('LILIYA_MARKETING_EMAIL', 'ocean2@shalibadra.com');
            $password = env('LILIYA_MARKETING_PASSWORD', 'Ocean2@123');
            $clientId = env('LILIYA_MARKETING_CLIENT_ID', 'dhruvi192');
            $templateName = env('LILIYA_GENERAL_TEMPLATE', 'shalibhadra_inquiry_general_msg');

            $auth = self::getLiliyaSessionToken($email, $password);
            if (empty($auth['token'])) {
                \Illuminate\Support\Facades\Log::error("Liliya General WhatsApp Auth Error: " . ($auth['error'] ?? 'Unknown'));
                return ['success' => false, 'message' => $auth['error'] ?? 'Auth failed'];
            }

            $sessionToken = $auth['token'];
            $activeClientId = $clientId ?: ($auth['client_id'] ?: 'dhruvi192');

            $parameters = [$customerName, $productName];

            $result = self::executeLiliyaSendRequest($cleanPhone, $templateName, $parameters, null, $activeClientId, $sessionToken);

            if (isset($result['http_code']) && $result['http_code'] == 401) {
                $auth = self::getLiliyaSessionToken($email, $password, true);
                if (!empty($auth['token'])) {
                    $result = self::executeLiliyaSendRequest($cleanPhone, $templateName, $parameters, null, $activeClientId, $auth['token']);
                }
            }

            if (!empty($result['success'])) {
                // Update inquiry counter and timestamps
                $newCount = ((int)($inquiry->whatsapp_general_sent_count ?? 0)) + 1;
                $inquiry->update([
                    'whatsapp_general_sent_count' => $newCount,
                    'last_whatsapp_general_sent_at' => now(),
                    'last_whatsapp_general_sent_by' => $userId,
                ]);

                // Create record in followups table so it shows in Followup List
                $defaultReason = \App\Models\FollowUpReason::where('company_id', $inquiry->company_id)->first() ?: \App\Models\FollowUpReason::first();
                $reasonId = $defaultReason ? $defaultReason->id : 1;
                $defaultStatus = \App\Models\MarketingStatus::where('company_id', $inquiry->company_id)->where('type', 'Followup')->first() ?: \App\Models\MarketingStatus::where('type', 'Followup')->first();
                $statusId = $inquiry->followup_status_id ?: ($defaultStatus ? $defaultStatus->id : 1);

                \App\Models\Followup::create([
                    'company_id' => $inquiry->company_id,
                    'inquiry_id' => $inquiry->id,
                    'date_time' => now(),
                    'reason_id' => $reasonId,
                    'through' => 'WhatsApp',
                    'details' => "General WhatsApp sent: {$templateName} (Count: {$newCount})",
                    'status_id' => $statusId,
                    'followup_action' => 'Next Follow-up',
                    'created_by' => $userId,
                    'created_type' => $createdType ?: 'Team',
                ]);

                // Create log in inquiry_logs
                \App\Models\InquiryLogs::create([
                    'company_id' => $inquiry->company_id,
                    'inquiry_id' => $inquiry->id,
                    'action' => 'General WhatsApp Sent',
                    'remarks' => "Template: {$templateName} to {$cleanPhone} (Sent Count: {$newCount})",
                    'json_data' => json_encode($result),
                    'created_by' => $userId ?: 'System',
                    'created_type' => $createdType ?: 'Team',
                ]);
            }

            \Illuminate\Support\Facades\Log::info("General WhatsApp sent for Inquiry #{$inquiry->inquiry_no} to {$cleanPhone}: " . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("General WhatsApp Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp template message with optional PDF attachment via Liliya API
     *
     * @param string $phone Customer mobile number
     * @param string $templateName Approved template name (e.g. 'shalibadra_enquiry')
     * @param array $parameters Positional parameters (e.g. [$name, $no, $date, $total])
     * @param string|null $filePath Absolute path to PDF file
     * @param string|null $clientId Liliya Client ID (default: 'SHALIB467')
     * @param string|null $documentName Display filename for attachment (e.g. 'Quotation.pdf')
     * @return array ['success' => bool, 'message' => string, 'clean_phone' => string]
     */
    public static function sendWhatsAppMessage($phone, $templateName = 'shalibadra_enquiry', array $parameters = [], $filePath = null, $clientId = null, $documentName = null, $email = null, $password = null)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $auth = self::getLiliyaSessionToken($email, $password, false, $clientId);
        if (empty($auth['token'])) {
            return [
                'success' => false,
                'message' => $auth['error'] ?? 'Failed to authenticate with Liliya API.',
                'clean_phone' => $cleanPhone
            ];
        }

        $activeClientId = $clientId ?: ($auth['client_id'] ?: 'SHALIB467');
        $sessionToken = $auth['token'];

        $result = self::executeLiliyaSendRequest($cleanPhone, $templateName, $parameters, $filePath, $activeClientId, $sessionToken, $documentName);

        // If 401 unauthorized (expired token), retry once with fresh token
        if (isset($result['http_code']) && $result['http_code'] == 401) {
            $auth = self::getLiliyaSessionToken($email, $password, true, $clientId);
            if (!empty($auth['token'])) {
                $result = self::executeLiliyaSendRequest($cleanPhone, $templateName, $parameters, $filePath, $activeClientId, $auth['token'], $documentName);
            }
        }

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Failed to send message via Liliya.',
            'clean_phone' => $cleanPhone,
            'message_id' => $result['message_id'] ?? null,
            'http_code' => $result['http_code'] ?? null,
            'raw_response' => $result['raw_response'] ?? null,
            'post_data' => $result['post_data'] ?? []
        ];
    }

    /**
     * Helper to execute Liliya send message cURL POST request
     */
    protected static function executeLiliyaSendRequest($phone, $templateName, array $parameters, $filePath, $clientId, $sessionToken, $documentName = null)
    {
        $url = "https://api.liliya.io/api/{$clientId}/send/message";
        $hasAttachment = !empty($filePath) && file_exists($filePath);

        $postData = [
            'message_name' => $templateName,
            'to' => $phone,
            'header_type' => $hasAttachment ? 'document' : 'text',
            'attachment_type' => $hasAttachment ? 'DOCUMENT' : '',
            'parameters' => json_encode(array_values($parameters))
        ];

        if ($hasAttachment) {
            $displayName = $documentName ?: basename($filePath);
            if (!str_ends_with(strtolower($displayName), '.pdf')) {
                $displayName .= '.pdf';
            }
            $postData['attachment_file'] = new \CURLFile($filePath, 'application/pdf', $displayName);
            $postData['attachment_filename'] = $displayName;
            $postData['filename'] = $displayName;
        }

        $headers = [
            "Authorization: Bearer {$sessionToken}",
            "Expect:"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            \Illuminate\Support\Facades\Log::error("Liliya Send cURL Error: {$curlError}");
            return [
                'success' => false,
                'http_code' => $httpCode,
                'message' => "cURL error: {$curlError}",
                'raw_response' => null,
                'post_data' => $postData
            ];
        }

        $resData = json_decode($response, true);
        \Illuminate\Support\Facades\Log::info("Liliya Send Response: " . json_encode($resData) . " | HTTP: {$httpCode}");

        $msgId = $resData['Message_ID'] ?? ($resData['message_id'] ?? ($resData['data']['message_id'] ?? null));

        if (isset($resData['success']) && (int)$resData['success'] === 1) {
            return [
                'success' => true,
                'http_code' => $httpCode,
                'message_id' => $msgId,
                'message' => $resData['message'] ?? 'Message sent successfully',
                'raw_response' => $resData,
                'post_data' => $postData
            ];
        }

        $errMsg = $resData['message'] ?? ($resData['error'] ?? 'WhatsApp API send failed.');
        return [
            'success' => false,
            'http_code' => $httpCode,
            'message_id' => $msgId,
            'message' => $errMsg,
            'raw_response' => $resData,
            'post_data' => $postData
        ];
    }
}
