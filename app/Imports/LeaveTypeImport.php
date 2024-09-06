<?php

namespace App\Imports;

use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeaveTypeImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return LeaveType::updateOrCreate([
            'name->en'  => $row['name_en'],
        ],[
            'name' => [
                'en' => $row['name_en'],
                'km' => $row['name_kh'],
            ],
            'abbr'      => $row['abbr'],
            'color'     => $row['color'],
            'male'      => $row['male'],
            'female'    => $row['female'],
            'balance'   => $row['balance'],
            'minimum_request_days'      => $row['minimum_request_days'],
            'balance_increment_period'  => $row['balance_increment_period'],
            'balance_increment_amount'  => $row['balance_increment_amount'],
            'maximum_balance'           => $row['maximum_balance'],
            'allow_carry_forward'       => $row['allow_carry_forward'],
            'carry_forward_duration'    => $row['carry_forward_duration'],
            'allow_advance'             => $row['allow_advance'],
            'advance_limit'             => $row['advance_limit'],
            'allow_accrual'             => $row['allow_accrual'],
            'visible'                   => $row['visible'],
        ]);
    }
}
