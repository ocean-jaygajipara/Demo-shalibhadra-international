<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerImportFile;
use App\Models\CustomerType;
use App\Models\MasterArea;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\PriceList;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CustomerImport implements ToCollection, WithHeadingRow
{
    protected $company_id;
    protected $loginUserId;
    protected $currentGuard;
    protected $customerImportFileId;

    public function __construct($company_id, $loginUserId, $currentGuard, $customerImportFileId)
    {
        $this->company_id = $company_id;
        $this->loginUserId = $loginUserId;
        $this->currentGuard = $currentGuard;
        $this->customerImportFileId = $customerImportFileId;

        // dd([
        //     'company_id' => $this->company_id,
        //     'loginUserId' => $this->loginUserId,
        //     'currentGuard' => $this->currentGuard,
        //     'customerImportFileId' => $this->customerImportFileId,
        // ]);
    }

    public function collection(Collection $collection)
    {
        Log::info('CustomerImport collection called');

        $totalCustomers = 0;
        $totalSuccessCustomers = 0;
        $totalFailedCustomers = 0;
        $errors = [];

        foreach ($collection as $index => $row) {
            if ($row->filter()->isEmpty()) continue;

            $totalCustomers++;
            $rowNumber = $index + 2;
            $validationErrors = [];

            // Read and clean values
            $customer_type         = trim($row['customer_type'] ?? '');
            $parent_name           = trim($row['parent_customer_name'] ?? '');
            $gst_number            = trim($row['gst_number'] ?? '');
            $customer_name         = trim($row['customer_name']);
            $contact_person_name   = trim($row['contact_person_name'] ?? '');
            $mobile_number         = trim($row['mobile_number'] ?? '');
            $whatsapp_number       = trim($row['whatsapp_number'] ?? '');
            $client_code           = trim($row['client_code'] ?? '');
            $country_name          = trim($row['country'] ?? '');
            $state_name            = trim($row['state'] ?? '');
            $city_name             = trim($row['city'] ?? '');
            $area_name             = trim($row['area'] ?? '');
            $pincode               = trim($row['pincode'] ?? '');
            $price_list_name       = trim($row['price_list'] ?? '');
            $address               = trim($row['address'] ?? '');
            $shipping_address      = trim($row['shipping_address'] ?? '');
            $billing_address       = trim($row['billing_address'] ?? '');
            $email                 = trim($row['email_id'] ?? '');
            $birth_date        = trim($row['birth_date'] ?? '');
            $payment_terms   = trim($row['payment_terms'] ?? '');
            $opening_balance = trim($row['opening_balance'] ?? '');
            $balance_type    = trim($row['balance_type'] ?? '');


            // dd($row->toArray());
            // Lookup Customer Type ID
            $customerType = null;
            if (!empty($customer_type)) {
                $customerType = CustomerType::where('name', $customer_type)
                    ->where('company_id', $this->company_id)
                    ->first();

                if (!$customerType) {
                    $validationErrors[] = "Customer Type '$customer_type' not found for this company";
                }
            } else {
                $validationErrors[] = "Customer Type is required";
            }

            if (empty($customer_name)) $validationErrors[] = "Customer Name is required";
            if (empty($contact_person_name)) $validationErrors[] = "Contact Person Name is required";
            if (empty($mobile_number)) $validationErrors[] = "Mobile Number is required";
            if (empty($address)) $validationErrors[] = "Address is required";
            if (empty($client_code)) $validationErrors[] = "Client Code is required";




            // === Country ===
            $country = null;
            if (!empty($country_name)) {
                $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower(trim($country_name))])->first();
                // if (!$country) {
                // $errors[] = "Row $rowNumber: Country '$country_name' not found, setting country_id as null";
                // }
            } else {
                $validationErrors[] = "Country is required";
            }



            // === State ===
            $state = null;
            if (!empty($state_name)) {
                if ($country) {
                    $state = MasterState::whereRaw('LOWER(name) = ?', [strtolower(trim($state_name))])
                        ->where('country_id', $country->id)
                        ->first();

                    if (!$state) {
                        Log::warning("Row $rowNumber: State '$state_name' not found for country '$country_name' — setting state_id as null.");
                    }
                }
            } else {
                $validationErrors[] = "State is required";
            }

            // === City ===
            $city = null;
            if (!empty($city_name)) {
                if ($state) {
                    $city = MasterCity::whereRaw('LOWER(name) = ?', [strtolower($city_name)])
                        ->where('state_id', $state->id)
                        ->first();
                    if (!$city) {
                        Log::warning("Row $rowNumber: City '$city_name' not found for state '$state_name' — setting city_id and area_id as null.");
                    }
                }
            }

            // === Area ===
            $area = null;
            if (!empty($area_name)) {
                if ($city) {
                    $area = MasterArea::whereRaw('LOWER(area_name) = ?', [strtolower($area_name)])
                        ->where('city_id', $city->id)
                        ->first();
                    if (!$area) {
                        Log::warning("Row $rowNumber: Area '$area_name' not found for city '$city_name' — setting area_id as null.");
                    }
                }
            }

            // === paymentTerm ===
            $paymentTerm = null;
            $credit_days = null;

            if (!empty($payment_terms)) {
                $paymentTerm = \App\Models\PaymentTerm::where('company_id', $this->company_id)
                    ->where('payment_terms', $payment_terms)
                    ->first();

                if ($paymentTerm) {
                    $credit_days = $paymentTerm->credit_days;
                }




                $priceList = null;
                if (!empty($price_list_name)) {
                    $priceList = PriceList::where('company_id', $this->company_id)
                        ->where('name', $price_list_name)
                        ->first();
                    // If not found, skip silently and let it be null
                }
                $parentCustomer = $parent_name ? Customer::where('customer_name', $parent_name)->where('company_id', $this->company_id)->first() : null;
                if ($parent_name && !$parentCustomer) $validationErrors[] = "Parent Customer '$parent_name' not found";

                // Handle birth date
                $birth_date_raw = trim($row['birth_date'] ?? '');
                $birth_date = null;

                if (!empty($birth_date_raw)) {
                    try {
                        if (is_numeric($birth_date_raw)) {
                            // Excel serialized date format
                            $birth_date = Carbon::instance(Date::excelToDateTimeObject($birth_date_raw));
                        } else {
                            // Try common formats
                            $birth_date = Carbon::createFromFormat('d-m-Y', $birth_date_raw);
                        }
                    } catch (\Exception $e) {
                        try {
                            // Try fallback to default parsing
                            $birth_date = Carbon::parse($birth_date_raw);
                        } catch (\Exception $e2) {
                            $validationErrors[] = "Invalid birth date format: '$birth_date_raw'";
                        }
                    }
                }


                // Skip row on validation error
                if (!empty($validationErrors)) {
                    $totalFailedCustomers++;
                    $errors[] = "Row $rowNumber: " . implode(', ', $validationErrors);
                    continue;
                }
                $existingCustomer = Customer::where('company_id', $this->company_id)
                    ->where(function ($query) use ($mobile_number, $client_code) {
                        $query->where('mobile_number', $mobile_number)
                            ->orWhere('client_code', $client_code);
                    })
                    ->first();

                if ($existingCustomer) {
                    $duplicateFields = [];

                    if ($existingCustomer->mobile_number === $mobile_number) {
                        $duplicateFields[] = "Mobile Number '$mobile_number'";
                    }

                    if ($existingCustomer->client_code === $client_code) {
                        $duplicateFields[] = "Client Code '$client_code'";
                    }

                    $totalFailedCustomers++;
                    $errors[] = "Row $rowNumber: Duplicate entry found for " . implode(' and ', $duplicateFields);
                    continue;
                }
                $last4Digits = substr($mobile_number, -4);

                // Generate SP and hashed password
                $sp = Str::random(4) . $last4Digits . Str::random(3);
                $password = Hash::make($last4Digits);

                // Create customer
                try {
                    Log::info('Created Type:', [
                        'guard' => $this->currentGuard,
                        'created_type' => ($this->currentGuard === 'team_person') ? 'Team' : (($this->currentGuard === 'admin_software') ? 'Admin' : null),
                    ]);
                    $data = [
                        'company_id'           => $this->company_id,
                        'customer_name'        => $customer_name,
                        'customer_type_id'     => optional($customerType)->id,
                        'parent_id'            => optional($parentCustomer)->id,
                        'price_list_id'        => optional($priceList)->id,
                        'gst_number'           => $gst_number,
                        'contact_person_name'  => $contact_person_name,
                        'mobile_number'        => $mobile_number,
                        'whatsapp_number'      => $whatsapp_number,
                        'client_code'          => $client_code,
                        'country_id'           => optional($country)->id,
                        'state_id'             => optional($state)->id,
                        'city_id'              => optional($city)->id,
                        'area_id'              => optional($area)->id,
                        'payment_term_id' => optional($paymentTerm)->id,
                        'credit_days'          => $credit_days,
                        'opening_balance'      => $opening_balance,
                        'balance_type'         => $balance_type,
                        'pincode'              => $pincode,
                        'address'              => $address,
                        'shipping_address'     => $shipping_address,
                        'billing_address'      => $billing_address,
                        'email'                => $email,
                        'sp'                   => $sp,
                        'password'             => $password,    
                        'birth_date'           => $birth_date,
                        'status'               => 'active',
                        'created_by'           => $this->loginUserId,
                        'created_type'         => ($this->currentGuard === 'team_person') ? 'Team' : (($this->currentGuard === 'admin_software') ? 'Admin' : null),
                    ];
                    // dd($data);

                    Customer::create($data);

                    $totalSuccessCustomers++;
                } catch (\Exception $e) {
                    $totalFailedCustomers++;
                    $errors[] = "Row $rowNumber: Insert failed - " . $e->getMessage();
                }


                // Update import summary

            }

            // Update import summary
            CustomerImportFile::where('id', $this->customerImportFileId)->update([
                'total_customers'         => $totalCustomers,
                'total_success_customers' => $totalSuccessCustomers,
                'total_failed_customers'  => $totalFailedCustomers,
                'errors'                  => json_encode($errors),
            ]);

            // dd($totalCustomers, $totalSuccessCustomers, $totalFailedCustomers);
        }
    }
}
