<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegisterCompanyController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        $this->modules = [
            'title' => 'Register Company',
            'folder_path' => 'software.modules.company.register',
            'route' => 'master-city',
            'table_name' => (new MasterCity())->getTable(),
            'permisstion_prefix' => 'master-city',
        ];

        // $this->middleware('permission:permissions-list|permissions-create|permissions-edit|permissions-delete', ['only' => ['index','show']]);
        // $this->middleware('permission:' . $this->modules['permisstion_prefix'] . '-list', ['only' => ['index', 'show']]);
        // $this->middleware('permission:' . $this->modules['permisstion_prefix'] . '-create', ['only' => ['create', 'store']]);
        // $this->middleware('permission:' . $this->modules['permisstion_prefix'] . '-edit', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:' . $this->modules['permisstion_prefix'] . '-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ($id = "print") {
            return self::print();
        }
        dd($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
