<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\Inquiry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InquiryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $srNo = 1;
    protected $filters;
    protected $user;
    protected $modules;

    public function __construct($filters = [], $user = null, $modules = [])
    {
        $this->filters = (object) $filters;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {

        $query = Inquiry::with([
            'inquiry_status',
            'followup_status',
            'source_of_inquiry',
            'created_by_name',
            'company'
        ]);

        if ($this->filters->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', '%' . $this->filters->search . '%')
                    ->orWhere('mobile_no', 'like', '%' . $this->filters->search . '%')
                    ->orWhere('contact_person_name', 'like', '%' . $this->filters->search . '%');
            });
        }

        if (!empty($this->filters->filter_company)) {
            $query->where('company_id', $this->filters->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        $mapFilters = [
            'filter_company' => 'company_id',
            'filter_inquiry_status' => 'inquiry_status_id',
            'filter_followup_status' => 'followup_status_id',
            'filter_source_of_inquiry' => 'source_of_inquiry_id',
            'filter_created_by' => 'inquiry_created_by',
            'filter_assigned_to' => 'inquiry_assigned_to',
        ];

        foreach ($mapFilters as $filterKey => $dbColumn) {
            if (!empty($this->filters->{$filterKey})) {
                $query->where($dbColumn, $this->filters->{$filterKey});
            }
        }

        if ($this?->filters?->company_id) {
            $query->where('company_id', $this?->filters?->company_id);
        }

        if ($this->filters?->filter_inquiry_date) {

            $filter_inquiry_date = $this->filters?->filter_inquiry_date;
            $filter_inquiry_date = explode(" to ", $filter_inquiry_date);
            $fromDate = null;
            $toDate = null;

            if (count($filter_inquiry_date) == 2) {
                $fromDate = Helper::convert_date($filter_inquiry_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($filter_inquiry_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', ">=", $fromDate);
                $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new Inquiry())->getTable() . '.inquiry_date', $fromDate);
            }
        }

        if (!empty($this->filters->filter_followup_date)) {
            $query->whereDate('followup_date', $this->filters->filter_followup_date);
        }
        if (!empty($this->filters->inquiry_created_by)) {
            $query->where('inquiry_created_by', $this->filters->inquiry_created_by);
        }
        if (!empty($this->filters->filter_created_by)) {
            $query->where('created_by', $this->filters->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }
        $query = $query->latest();
        // dd("L-86", $this->filters, Helper::interpolateQuery($query->toSql(), $query->getBindings()));
        $query = $query->get();
        return $query;
    }

    public function map($inquiry): array
    {

        $assignedNames = $inquiry->assigned_to_name;

        $assignedToText = is_array($assignedNames)
            ? implode(', ', $assignedNames)
            : ($inquiry->assigned_to_name ?? '-');
        $row = [
            $this->srNo++,
            $inquiry->inquiry_no ?? '-',
            $inquiry->inquiry_date ?? '-',
            optional($inquiry->inquiry_status)->name ?? '-',
            $inquiry->customer_name ?? '-',
            $inquiry->contact_person_name,
            optional($inquiry->followup_status)->name ?? '-',
            $inquiry->mobile_no ?? '-',
            $inquiry->whatsapp_no ?? '-',
            $inquiry->email ?? '-',
            optional($inquiry->source_of_inquiry)->name ?? '-',
            $inquiry->requirement_details ?? '-',
            optional($inquiry->country)->name ?? '-',
            optional($inquiry->state)->name ?? '-',
            optional($inquiry->city)->name ?? '-',
            $inquiry->created_by_name ?? '-',
            $assignedToText ?? '-',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($row, 1, 0, optional($inquiry->company)->company_name ?? '-');
        }

        return $row;
    }


    public function headings(): array
    {

        $headings = [
            'Sr No',
            'Inquiry No',
            'Inquiry Date',
            'Inquiry Status',
            'Customer Name',
            'Contact Person Name',
            'Followup Status',
            'Mobile No.',
            'WhatsApp No',
            'Email',
            'Source of Inquiry',
            'Requirement Details',
            'Country',
            'State',
            'City',
            'Created By',
            'Assigned To',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Inquiries';
    }
}
