<?php

namespace App\Imports;

use App\Models\PublicHoliday;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PublicHolidayImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return PublicHoliday::updateOrCreate([
            'date'  => $row['date'],
        ],[
            'name' => [
                'en' => $row['name_en'],
                'km' => $row['name_km'],
            ],
            'date' => $row['date'],
        ]);
    }
}
