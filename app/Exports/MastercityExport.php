<?php

namespace App\Exports;

use App\Models\MasterCity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MastercityExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $srNo = 0; // Counter for Sr No

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = MasterCity::with(['country', 'state'])->withTrashed();

        // Apply filters
        if (!empty($this->filters['search'])) {
            $query->where('name', 'like', '%' . $this->filters['search'] . '%');
        }

        if (!empty($this->filters['country_id'])) {
            $query->where('country_id', $this->filters['country_id']);
        }

        if (!empty($this->filters['state_id'])) {
            $query->where('state_id', $this->filters['state_id']);
        }

        if (isset($this->filters['status']) && $this->filters['status'] !== '' && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('id', 'DESC')->get();
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Country Name',
            'State Name',
            'City Name',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->srNo,
            optional($row->country)->name ?? 'N/A',
            optional($row->state)->name ?? 'N/A',
            $row->name,
            ucfirst($row->status),
        ];
    }
}
