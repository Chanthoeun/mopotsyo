<?php

namespace App\Imports;

use App\Models\ContractType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContractTypeImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return ContractType::updateOrCreate([
            'name->en'  => $row['name_en'],
        ],[
            'name' => [
                'en' => $row['name_en'],
                'km' => $row['name_kh'],
            ],
            'abbr' => $row['abbr'],
            'allow_leave_request' => strtolower($row['allow_leave_request']) == 'yes' ? true : false,
        ]);
    }
}
