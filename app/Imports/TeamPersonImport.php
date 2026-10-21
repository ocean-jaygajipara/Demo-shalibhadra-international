<?php

namespace App\Imports;

use App\Models\Designation;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\TeamPerson;
use App\Models\TeamPersonImportFile;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class TeamPersonImport implements ToCollection, WithHeadingRow
{
    protected $company_id;
    protected $loginUserId;
    protected $currentGuard;
    protected $customerImportFileId;
    private function formatExcelTime($value)
    {
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('H:i');
            } catch (\Exception $e) {
                return null;
            }
        }

        return !empty($value) ? date('H:i', strtotime($value)) : null;
    }
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
        Log::info('TeamPersonImport collection called');

        $totalTeampersons = 0;
        $totalSuccessTeampersons = 0;
        $totalFailedTeampersons = 0;
        $errors = [];

        foreach ($collection as $index => $row) {
            if ($row->filter()->isEmpty()) continue;

            $totalTeampersons++;
            $rowNumber = $index + 2;
            $validationErrors = [];

            $team_person_name                  = trim($row['team_person_name'] ?? '');
            $parent_person_name = trim($row['parent_person_name'] ?? '');
            $mobile_no             = trim($row['mobile_no'] ?? '');
            $employee_code         = trim($row['employee_code'] ?? '');
            $username              = trim($row['username'] ?? '');
            $password              = trim($row['password'] ?? '');
            $min_working_start     = $this->formatExcelTime($row['min_working_start_time'] ?? '');
            $max_working_start     = $this->formatExcelTime($row['max_working_start_time'] ?? '');
            $working_end           = $this->formatExcelTime($row['working_end_time'] ?? '');
            $country_name          = trim($row['country'] ?? '');
            $state_name            = trim($row['state'] ?? '');
            $city_name             = trim($row['city'] ?? '');
            $area_name             = trim($row['area'] ?? '');
            $address               = trim($row['address'] ?? '');
            $email                 = trim($row['email'] ?? '');
            $designation = trim($row['designation'] ?? '');
            $birth_date_raw = $row['birth_date'] ?? '';
            $birth_date = null;

            if (is_numeric($birth_date_raw)) {
                try {
                    $birth_date = Date::excelToDateTimeObject($birth_date_raw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $birth_date = null;
                }
            } elseif (!empty($birth_date_raw)) {
                $birth_date = date('Y-m-d', strtotime($birth_date_raw));
            }

            // dd($row->toArray());
            // Validation
            if (empty($team_person_name)) $validationErrors[] = "Team Person Name is required";
            if (empty($mobile_no)) $validationErrors[] = "Mobile No is required";
            if (empty($employee_code)) $validationErrors[] = "Employee Code is required";
            if (empty($username)) $validationErrors[] = "Username is required";
            if (empty($password)) $validationErrors[] = "Password is required";
            if (empty($min_working_start)) $validationErrors[] = "Min Working Start Time is required";
            if (empty($max_working_start)) $validationErrors[] = "Max Working Start Time is required";
            if (empty($working_end)) $validationErrors[] = "Working End Time is required";
            if (empty($address)) $validationErrors[] = "Address is required";

            // === Country ===
            $country = null;
            if (empty($country_name)) {
                $validationErrors[] = "Country is required";
            } else {
                $country = MasterCountry::whereRaw('LOWER(name) = ?', [strtolower(trim($country_name))])->first();
            }

            // === State ===
            $state = null;
            if (empty($state_name)) {
                $validationErrors[] = "State is required";
            } elseif ($country) {
                $state = MasterState::whereRaw('LOWER(name) = ?', [strtolower(trim($state_name))])
                    ->where('country_id', $country->id)
                    ->first();
            }

            // === City ===
            $city = null;
            if (empty($city_name)) {
                $validationErrors[] = "City is required";
            } elseif ($state) {
                $city = MasterCity::whereRaw('LOWER(name) = ?', [strtolower($city_name)])
                    ->where('state_id', $state->id)
                    ->first();
            }

            // === Area ===
            $area = null;
            if (empty($area_name)) {
                $validationErrors[] = "Area is required";
            } elseif ($city) {
                $area = MasterArea::whereRaw('LOWER(area_name) = ?', [strtolower($area_name)])
                    ->where('city_id', $city->id)
                    ->first();
            }

            $designation_id = null;
            if (!empty($designation)) {
                $designation_id = Designation::where('name', $designation)->value('id');
                // if (!$designation_id) {
                //     $validationErrors[] = "Designation '$designation' not found";
                // }
            }



            $parent_type_id = null;
            if (!empty($parent_person_name)) {
                $parent = TeamPerson::where('name', $parent_person_name)
                    ->where('company_id', $this->company_id)
                    ->first();

                if ($parent) {
                    $parent_type_id = $parent->id;
                }
                // If not found, leave $parent_type_id as null (no error thrown)
            }


            if (!empty($validationErrors)) {
                $totalFailedTeampersons++;
                $errors[] = "Row $rowNumber: " . implode(', ', $validationErrors);
                continue;
            }
            $existingUsername = TeamPerson::where('username', $username)
                ->where('company_id', $this->company_id)
                ->first();
            if ($existingUsername) {
                $totalFailedTeampersons++;
                $errors[] = "Row $rowNumber: Username '$username' already exists.";
                continue;
            }

            try {
                TeamPerson::create([
                    'company_id'              => $this->company_id,
                    'name'                    => $team_person_name,
                    'parent_type_id' => $parent_type_id,
                    'mobile_no'               => $mobile_no,
                    'employee_code'           => $employee_code,
                    'username'                => $username,
                    'password'                => bcrypt($password),
                    'sp'                      => Str::random(4) . $password . Str::random(3),
                    'min_working_start_time'  => $min_working_start,
                    'max_working_start_time'  => $max_working_start,
                    'working_end_time'        => $working_end,
                    'country_id' => $country?->id,
                    'state_id'   => $state?->id,
                    'city_id'    => $city?->id,
                    'area_id'    => $area?->id,
                    'address'                 => $address,
                    'email'                   => $email,
                    'designation_id'          => $designation_id,
                    'birth_date'              => $birth_date,
                    'created_by'              => $this->loginUserId,
                    'created_type'            => ($this->currentGuard === 'team_person') ? 'Team' : 'Admin',
                ]);
//                 dd([
//     'company_id'              => $this->company_id,
//     'name'                    => $team_person_name,
//     'parent_type_id'          => $parent_type_id,
//     'mobile_no'               => $mobile_no,
//     'employee_code'           => $employee_code,
//     'username'                => $username,
//     'password'                => bcrypt($password),
//     'sp'                      => Str::random(4) . $password . Str::random(3),
//     'min_working_start_time' => $min_working_start,
//     'max_working_start_time' => $max_working_start,
//     'working_end_time'       => $working_end,
//     'country_id'              => $country?->id,
//     'state_id'                => $state?->id,
//     'city_id'                 => $city?->id,
//     'area_id'                 => $area?->id,
//     'address'                 => $address,
//     'email'                   => $email,
//     'designation_id'          => $designation_id,
//     'birth_date'              => $birth_date,
//     'created_by'              => $this->loginUserId,
//     'created_type'            => ($this->currentGuard === 'team_person') ? 'Team' : 'Admin',
// ]);

                // dd($designation_id);

                $totalSuccessTeampersons++;
            } catch (\Exception $e) {
                $totalFailedTeampersons++;
                $errors[] = "Row $rowNumber: Insert failed - " . $e->getMessage();
            }
        }

        TeamPersonImportFile::where('id', $this->customerImportFileId)->update([
            'total_team_persons'         => $totalTeampersons,
            'total_success_team_persons' => $totalSuccessTeampersons,
            'total_failed_team_persons'  => $totalFailedTeampersons,
            'errors'                     => json_encode($errors),
        ]);
    }
}
