<?php

namespace App\Imports;

use App\Models\Inquiry;
use App\Models\InquiryImportFile;
use App\Models\MarketingStatus;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\SourceOfInquiry;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InquiryImport implements ToCollection, WithHeadingRow
{
    protected $company_id;
    protected $loginUserId;

    protected $currentGuard;
    protected $inquiryImportFileId;

    public function __construct($company_id, $loginUserId, $currentGuard, $inquiryImportFileId)
    {
        $this->company_id = $company_id;
        $this->loginUserId = $loginUserId;
        $this->inquiryImportFileId = $inquiryImportFileId;
        $this->currentGuard = $currentGuard;

        // dd([
        //     'company_id' => $this->company_id,
        //     'loginUserId' => $this->loginUserId,
        //     'currentGuard' => $this->currentGuard,
        //     'inquiryImportFileId' => $this->inquiryImportFileId,
        // ]);
    }

    public function collection(Collection $collection)
    {

        Log::info('InquiryImport collection started, total rows: ' . $collection->count());

        $inquiryImportFileId = $this->inquiryImportFileId;
        $totalInquiries = 0;
        $totalSuccessInquiries = 0;
        $totalFailedInquiries = 0;
        $errors = [];

        foreach ($collection as $index => $row) {
            Log::info("Processing row " . ($index + 2));
            if ($row->filter()->isEmpty()) continue;

            $totalInquiries++;
            $rowNumber = $index + 2;

            // Read and trim values
            $customer_name        = trim($row['customer_name'] ?? '');
            $contact_person_name  = trim($row['contact_person_name'] ?? '');
            $mobile_no            = trim($row['mobile_no'] ?? '');
            $whatsapp_no          = trim($row['whatsapp_no'] ?? '');
            $email                = trim($row['email'] ?? '');
            $website              = trim($row['website'] ?? '');
            $address              = trim($row['address'] ?? '');
            $country_name         = trim($row['country'] ?? '');
            $state_name           = trim($row['state'] ?? '');
            $city_name            = trim($row['city'] ?? '');
            $area_name            = trim($row['area'] ?? '');
            $requirement_details  = trim($row['requirement_details'] ?? '');
            $source_of_inquiry    = trim($row['source_of_inquiry'] ?? 'Self');
            $inquiry_status       = trim($row['inquiry_status'] ?? '');
            $followup_status      = trim($row['followup_status'] ?? '');
            $inquiry_created_by   = trim($row['created_by'] ?? '');
            $inquiry_assigned_to  = trim($row['assigned_to'] ?? '');
            $validationErrors = [];

            if (empty($customer_name)) $validationErrors[] = 'Customer Name is missing';
            if (empty($mobile_no))     $validationErrors[] = 'Mobile No is missing';

            // Handle Inquiry Date
            if (empty($row['inquiry_date'])) {
                $validationErrors[] = 'Inquiry Date is missing';
                $inquiry_date = null;
            } else {
                try {
                    if (is_numeric($row['inquiry_date'])) {
                        $inquiry_date = Carbon::instance(Date::excelToDateTimeObject($row['inquiry_date']));
                    } else {
                        $inquiry_date = Carbon::createFromFormat('m-d-Y', $row['inquiry_date']);
                    }
                } catch (\Exception $e) {
                    $inquiry_date = null;
                    $validationErrors[] = "Invalid Inquiry Date format";
                }
            }
            // dd($collection->toArray());
            // Lookup and validate foreign keys
            $country = $country_name ? MasterCountry::where('name', $country_name)->first() : null;
            if ($country_name && !$country) $validationErrors[] = "Country '$country_name' not found";

            $state = $state_name ? MasterState::where('name', $state_name)->where('country_id', optional($country)->id)->first() : null;
            if ($state_name && !$state) $validationErrors[] = "State '$state_name' not found";

            $city = $city_name ? MasterCity::where('name', $city_name)->where('state_id', optional($state)->id)->where('country_id', optional($country)->id)->first() : null;
            if ($city_name && !$city) $validationErrors[] = "City '$city_name' not found";

            $area = $area_name ? MasterArea::where('area_name', $area_name)->where('city_id', optional($city)->id)->where('company_id', $this->company_id)->first() : null;
            if ($area_name && !$area) $validationErrors[] = "Area '$area_name' not found";

            // ✅ Validate if field is empty — show validation error
            if (empty($source_of_inquiry)) {
                $validationErrors[] = "Source of Inquiry is missing";
                $source = null;
            } else {
                // ✅ Lookup — skip silently if not found
                $source = SourceOfInquiry::where('company_id', $this->company_id)
                    ->where('name', $source_of_inquiry)
                    ->first();
            }

            if (empty($inquiry_status)) {
                $validationErrors[] = "Inquiry Status is missing";
                $inquiryStatus = null;
            } else {
                $inquiryStatus = MarketingStatus::where('company_id', $this->company_id)
                    ->where('type', 'Inquiry')
                    ->where('name', $inquiry_status)
                    ->first();
            }

            if (empty($followup_status)) {
                $validationErrors[] = "Followup Status is missing";
                $followupStatus = null;
            } else {
                $followupStatus = MarketingStatus::where('company_id', $this->company_id)
                    ->where('type', 'Followup')
                    ->where('name', $followup_status)
                    ->first();
            }

            $created_by_user = $inquiry_created_by ? TeamPerson::where('name', $inquiry_created_by)->first() : null;
            if ($inquiry_created_by && !$created_by_user) $validationErrors[] = "Inquiry Created By '$inquiry_created_by' not found";

            $assigned_by = $inquiry_assigned_to ? TeamPerson::where('name', $inquiry_assigned_to)->first() : null;
            if ($inquiry_assigned_to && !$assigned_by) $validationErrors[] = "Inquiry Assigned To '$inquiry_assigned_to' not found";

            if (!empty($validationErrors)) {
                Log::warning("Row " . ($index + 2) . " validation errors: " . implode(', ', $validationErrors));

                $totalFailedInquiries++;
                $errors[] = "Row $rowNumber: " . implode(', ', $validationErrors);
                // dd($errors);
                continue;
            }

            try {
                $followup_date = !empty($row['followupdate'])
                    ? (is_numeric($row['followupdate'])
                        ? Carbon::instance(Date::excelToDateTimeObject($row['followupdate']))
                        : Carbon::parse($row['followupdate']))
                    : now();

                $inquiryCount = Inquiry::withTrashed()->where('company_id', $this->company_id)->count();
                $inquiryNo = 'INQ-' . str_pad($inquiryCount + 1, 3, '0', STR_PAD_LEFT);
                $data = [
                    'company_id'            => $this->company_id,
                    'inquiry_no'            => $inquiryNo,
                    'customer_name'         => $customer_name,
                    'contact_person_name'   => $contact_person_name,
                    'mobile_no'             => $mobile_no,
                    'whatsapp_no'           => $whatsapp_no,
                    'email'                 => $email,
                    'address'               => $address,
                    'website'               => $website,
                    'requirement_details'   => $requirement_details,
                    'country_id'            => optional($country)->id,
                    'state_id'              => optional($state)->id,
                    'city_id'               => optional($city)->id,
                    'area_id'               => optional($area)->id,
                    'inquiry_date'          => $inquiry_date?->format('Y-d-m'),
                    'followup_date'         => $followup_date->format('Y-m-d'),
                    'inquiry_created_by'    => optional($created_by_user)->id,
                    'inquiry_assigned_to'   => optional($assigned_by)->id,
                    'created_by'            => $this->loginUserId,
                    'created_type' => ($this->currentGuard === 'team_person') ? 'Team' : (($this->currentGuard === 'admin_software') ? 'Admin' : null),
                    'platform'              => 'Import Inquiry',
                    // 'updated_by'            => $this->loginUserId,
                ];


                if ($source) $data['source_of_inquiry_id'] = $source->id;
                if ($inquiryStatus) $data['inquiry_status_id'] = $inquiryStatus->id;
                if ($followupStatus) $data['followup_status_id'] = $followupStatus->id;
                //    dd($data);

                Inquiry::create($data);

                $totalSuccessInquiries++;
                Log::info("Row " . ($index + 2) . " inserted successfully.");
            } catch (\Exception $e) {
                Log::error("Row " . ($index + 2) . " insert failed: " . $e->getMessage());

                $totalFailedInquiries++;
                $errors[] = "Row $rowNumber: Insert failed - " . $e->getMessage();
            }
                Log::info('InquiryImport collection ended.');

        }

        InquiryImportFile::where('id', $inquiryImportFileId)->update([
            'total_inquiries'          => $totalInquiries,
            'total_success_inquiries'  => $totalSuccessInquiries,
            'total_failed_inquiries'   => $totalFailedInquiries,
            'errors'                   => json_encode($errors),
            // 'updated_by'               => $this->loginUserId,
        ]);
    }
}
