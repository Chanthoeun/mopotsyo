<?php

namespace App\Imports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DepartmentImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return Department::updateOrCreate([
            'name->en'  => $row['name_en'],
        ],[
            'name' => [
                'en' => $row['name_en'],
                'km' => $row['name_kh'],
            ],
            'parent_id' => Department::where('name->en', $row['parent'])->first()?->id ?? null,
        ]);
    }
}
