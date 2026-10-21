<?php

namespace App\Exports;

use App\Models\AdminSoftware;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;


class UserExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $modules;
    protected $loginUserId;

     public function __construct($filter_params = null, $user = null, $modules = null, $loginUserId = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
        $this->loginUserId = $loginUserId;
    }
    public function collection()
    {
        $query = AdminSoftware::whereIn('type', ['admin_user', 'office_user'])->whereNull('deleted_at');
        if (!empty($this->modules['personal_data_permission']) && empty($this->modules['all_data_permission'])) {
            $query->where('created_by', $this->loginUserId);
        }


        if ($this->filter_params?->search && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('username', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%");
            });
        }

        if ($this->filter_params?->status && $this->filter_params?->status !== null && $this->filter_params?->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }
        if ($this->filter_params?->type && $this->filter_params?->type !== null && $this->filter_params?->type !== 'all') {
            $query->where('type', $this->filter_params->type);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Name',
            'User Name',
            'Type',
            'Email',
            'Phone',
            'Status',
        ];
        return $headings;
    }

    public function map($person): array
    {
         $rowData = [
            $this->srNo++,
            $person->name ?? '-',
            $person->username ?? '-',
            $person->type == 'admin_user' ? 'Admin User' : 'Office User',
            $person->email ?? '-',
            $person->phone ?? '-',
            $person->status ? 'Active' : 'Inactive',
        ];
        return $rowData;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'User';
    }
}
