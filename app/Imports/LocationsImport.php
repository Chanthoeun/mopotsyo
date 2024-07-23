<?php

namespace App\Imports;

use App\Models\Location;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocationsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // parent                 
        $parentCode = substr($row['code'], 0, (strlen($row['code']) - 2));
        $location = new Location();
        $location->parent_id =  !empty($parentCode) ? Location::where('code', substr($row['code'], 0, (strlen($row['code']) - 2)))->first()->id : null;
        $location->location_type_id = match(strlen($row['code'])) {
                                        2 => 1,
                                        4 => 2,
                                        6 => 3,
                                        8 => 4,
                                    };
        $location->code = $row['code'];
        $location->reference = $row['reference'];
        $location->note = $row['note'];
        $location->setTranslation('name', 'en', $row['english']);
        $location->setTranslation('name', 'km', $row['khmer']);
        $location->save();
        return $location;
    }
}
