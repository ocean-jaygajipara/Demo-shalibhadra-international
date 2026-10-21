<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\Followup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class FollowupExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;


    // Constructor to accept a search parameter
    public function __construct($filter_params = null, $user = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
    }
    // Collection method to fetch all data with necessary relationships
    public function collection()
    {
        $query = Followup::with([
            'inquiry',
            'inquiry.company',
            'teamPerson',
            'marketingStatus',
        ])->whereNull('followups.deleted_at');

        // Filter: Search on Inquiry fields
        if (!empty($this->filter_params->search)) {
            $search = $this->filter_params->search;
            $query->whereHas('inquiry', function ($iq) use ($search) {
                $iq->where('inquiry_no', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_no', 'like', '%' . $search . '%')
                    ->orWhere('contact_person_name', 'like', '%' . $search . '%');
            });
        }

        // Filter: Company (manual)
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        // Filter: Followup Status (assumed to be on Followup table)
        if (!empty($this->filter_params->filter_followup_status)) {
            $query->where('status_id', $this->filter_params->filter_followup_status);
        }

        // Filter: Created By
        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } else if ($this->user && $this->user?->company_id) {
            if ($this->user?->parent_type_id) {
                $query->where('created_by', $this->user?->id);
            }
        }

        // Filter: Followup Date Range
        if (!empty($this->filter_params->filter_followup_date)) {
            $dates = explode(" - ", $this->filter_params->filter_followup_date);
            if (count($dates) == 2) {
                $fromDate = Helper::convert_date($dates[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($dates[1], "d/m/Y", "Y-m-d");

                $query->whereDate('date_time', '>=', $fromDate)
                    ->whereDate('date_time', '<=', $toDate);
            }
        }



        // Optional: Filter by followup type (today, pending)
        if (!empty($this->filter_params->filter_by_followup_type)) {
            $today = Helper::trait_current_date("Y-m-d");

            if ($this->filter_params->filter_by_followup_type === 'today_all_follow_up') {
                $query->where(function ($q) use ($today) {
                    $q->whereDate('date_time', $today)
                        ->orWhereDate('created_at', $today);
                });
            } elseif ($this->filter_params->filter_by_followup_type === 'today_pending_all_follow_up') {
                $query->whereDate('date_time', $today)
                    ->whereNull('response_date_time');
            } elseif ($this->filter_params->filter_by_followup_type === 'pending_all_follow_up') {
                $query->whereNull('response_date_time');
            }
        }

        $query = $query->latest();
        // dd("L-86", $this->filter_params, Helper::interpolateQuery($query->toSql(), $query->getBindings()), $this->user?->toArray());
        $query = $query->get();
        return $query;
    }



    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Inquiry No',
            'Followup Status',
            'Team Person Name',
            'Customer Details',
            'Mobile No ',
            'Contact Person Name',
            'Description',
            'Followup Date & Time',
            'Created Date & Time',

        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }
        return $headings;
    }

    // Mapping each row of data to the respective Excel columns
    public function map($row): array
    {
        static $srNo = 1;

        $rowData =  [
            $srNo++,
            optional($row->inquiry)->inquiry_no,
            ucfirst(optional($row->marketingStatus)->name),
            optional($row->teamPerson)->name,
            optional($row->inquiry)->customer_name,
            $row->inquiry->mobile_no,
            $row->inquiry->contact_person_name,
            $row->details,
            $row->date_time,
            $row->created_at ? $row->created_at->format('Y-m-d H:i') : '',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rowData, 1, 0, optional($row->company)->company_name ?? '-');
        }
        return $rowData;
    }

    // Styling for the Excel sheet
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    // Title for the Excel sheet

    public function title(): string
    {
        return 'FollowUp';
    }
}
