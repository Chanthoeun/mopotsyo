<?php

namespace App\Imports;

use App\Models\LocationType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocationTypeImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return LocationType::updateOrCreate([
            'name->en'  => $row['name_en'],
        ],[
            'name' => [
                'en' => $row['name_en'],
                'km' => $row['name_kh'],
            ]
        ]);
    }
}
