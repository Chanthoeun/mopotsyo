<?php

// use App\Enums\ActionStatusEnum;
// use App\Enums\StatusEnum;
// use App\Enums\TripProgressEnum;
// use App\Models\HR\Employee;
// use App\Models\User;

use App\Enums\ActionStatusEnum;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Support\Collection;

if(!function_exists('getAcronym')){
    function getAcronym($string){        
        $words = explode(" ", $string);
        $acronym = "";

        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
        }
        
        $acronym = preg_replace('/[^A-Za-z0-9\-]/', '', $acronym); // Removes special chars.
        return $acronym;
    }
}

if(!function_exists('decimalToTime')){
    /**
     * Convert decimal time into time in the format hh:mm:ss
     *
     * @param integer The time as a decimal value.
     *
     * @return string $time The converted time value.
     */
    function decimalToTime($decimal) {
        $h = intval($decimal);
        $m = round((((($decimal - $h) / 100.0) * 60.0) * 100), 0);
        if ($m == 60)
        {
            $h++;
            $m = 0;
        }
        if($m == 0){
            $retval = sprintf("%2dh", $h);
        }else{
            $retval = sprintf("%2dh : %02dmin", $h, $m);
        }
        
        return $retval;
    }
}

if(!function_exists('getHoursBetweenTwoTimes')){
    function getHoursBetweenTwoTimes($stat_time, $end_time){
        $startTime  = Carbon::parse($stat_time);
        $endTime    = Carbon::parse($end_time);
        return $startTime->floatDiffInHours($endTime);
    }
}

if(!function_exists('getEntitlementBalance')){
    function getEntitlementBalance($jointDate, $leaveType) : int {
        $startDate = Carbon::parse($jointDate);
        $endDate  = Carbon::createFromDate(now()->year, $startDate->month, $startDate->day);
        $duration = $startDate->diffInYears($endDate);        
        $increment = 0;
        if(!empty($leaveType->balance_increment_amount) && !empty($leaveType->balance_increment_period)){                                                                            
            $increment =  intval($duration / floatval($leaveType->balance_increment_period));
        }

        $balance = intval($leaveType->balance + $increment);

        if(!empty($leaveType->maximum_balance) && $leaveType->maximum_balance > $balance){
            return $balance;
        }
        
        return $leaveType->maximum_balance;
    }
}

if(!function_exists('dayName')){
    function dayName($date){
        return Carbon::parse($date)->locale(config('app.locale'))->dayName;
    }
}

if(!function_exists('isWorkHour')){
    function isWorkHour($user, $date, $request_time): bool{
        foreach($user->profile->shift->workDays as $workDay){
            $startWorkHour = Carbon::parse($workDay->from_time);
            $endWorkHour = Carbon::parse($workDay->to_time);
            $requestTime = Carbon::parse($request_time);                                  
            if(strtolower($workDay->day) == strtolower(dayName($date)) && $requestTime->isBetween($startWorkHour, $endWorkHour, true)){
                return true;
            }
        }
        return false;
    }
}

if(!function_exists('isWeekend')){
    function isWeekend($date): bool{
        if(Carbon::parse($date)->isWeekend()){
            return true;
        }
        return false;        
    }
}


if(!function_exists('isPublicHoliday')){
    function isPublicHoliday($user, $date): bool{
        return $user->profile->law->publicHolidays->map(function($holiday){
            return [
                'date'  => Carbon::parse($holiday->date)->toDateString(),
                'name'  => $holiday->name
            ];
        })->contains('date', '=', Carbon::parse($date)->toDateString());
    }
}

if(!function_exists('getHolidayName')){
    function getHolidayName($user, $date): string{
        $holiday = $user->profile->law->publicHolidays->map(function($holiday){
            return [
                'date'  => Carbon::parse($holiday->date)->toDateString(),
                'name'  => $holiday->name
            ];
        })->where('date', Carbon::parse($date)->toDateString())->first();
        
        return $holiday['name'];
    }
}

if(! function_exists('getDateRangeBetweenTwoDates')){
    function getDateRangeBetweenTwoDates($startDate, $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        return $period->toArray();        
    }
}

if(!function_exists('checkDuplicatedLeaveRequest')){
    function checkDuplicatedLeaveRequest($user, $date){
        foreach($user->leaveRequests as $leaveRequest){
            foreach($leaveRequest->requestDates as $requestDate)
            {
                if($requestDate->date == $date){
                    return $requestDate;
                }
            }           
        }
        return false;
    }
}

if(!function_exists('checkDuplicatedDate')){
    function checkDuplicatedDate($user, $date) : string | null {
        $date = Carbon::parse($date); 
        
        // Is it weekend
        if($date->isWeekend() == true){
            return __('msg.is_weekend', ['date' => $date->toDateString(), 'name' => dayName($date)]);
        }

        // Is it public holiday
        if(isPublicHoliday($user, $date) == true){
            return __('msg.is_holiday', ['date' => $date->toDateString(), 'name' => getHolidayName($user, $date)]);
        }

        // check trip date
        // if(Auth::user()->trip_dates()->contains($date->toDateString()) == true){
        //     return __('msg.is_trip_day', ['date' => $date->toDateString()]);
        // }

        // check duplicated leave request
        foreach($user->leaveRequests as $leaveRequest){
            if($leaveRequest->requestDates->contains($date->toDateString())){
                return __('msg.is_duplicated', ['date' => $date->toDateString()]);
            }
        }

        return null;
    }
}

if(!function_exists('calculateAccrud'))
{
    function calculateAccrud($balance, $startDate, $endDate): float{
        $from   = Carbon::parse($startDate, config('app.timezone'));
        $to     = Carbon::parse($endDate, config('app.timezone'));
        $days   = $from->diffInDays($to);
        if($days > 0)
        {
            $perDay = ($balance / getDaysOfTheYear(now()->year));  
            $accrued = round(($days * $perDay), 2);
            return $accrued;
        }   
        return 0;
    }
}

if(!function_exists('getDaysOfTheYear')){
    function getDaysOfTheYear($year){        
        $startDateOfTheYear = Carbon::createFromDate($year, 1, 1);
        $endDateOfTheYear   = Carbon::createFromDate($year, 12, 31);
        return $startDateOfTheYear->diffInDays($endDateOfTheYear);
    }
}

if(!function_exists('getDaysFromHours')){
    function getDaysFromHours($user_id, $hours) : float {
        $user = User::with('profile.shift')->find($user_id);
        return round($hours / $user->profile->shift->work_hours, 1);
    }
}

if(!function_exists('getAddress')){
    function getAddress($addresses){
        $address = $addresses->where('is_primary', true)->first();
        return "{$address->address_1} {$address->address_2}, {$address->city}, ".getState($address->state).", ". getCountry($address->country).", {$address->postcode}";
    }
}

if(!function_exists('getCountry')){
    function getCountry($country){
        return CountryState::getCountryName($country);
    }
}

if(!function_exists('getState')){
    function getState($state){
        return CountryState::getStateName($state);
    }
}

if(!function_exists('getQuoteId')){
    function getQuoteId($quote){
        return 'QID-'.sprintf('%06d',$quote->id);
    }
}

// if(!function_exists('isSupervisor')){
//     function isSupervisor($user){
//         if(Auth::user()->supervisor()->id == $user->id){
//             return true;
//         }
//         return false;
//     }
// }







// if(!function_exists('rangeWeek')){
//     function rangeWeek ($date = false) {
//         date_default_timezone_set (date_default_timezone_get());
//         $dt = strtotime($date == false ? date('Y-m-d') : $date);
//         $startDate = date ('N', $dt) == 1 ? date ('Y-m-d', $dt) : date ('Y-m-d', strtotime ('last monday', $dt));
//         $endDate = date('N', $dt) == 7 ? date ('Y-m-d', $dt) : date ('Y-m-d', strtotime ('next sunday', $dt));

//         return getDateRangeBetweenTwoDates($startDate, $endDate);
//     }
// }

// if(!function_exists('rangeMonth')){
//     function rangeMonth ($date = false) {
//         date_default_timezone_set (date_default_timezone_get());
//         $dt = strtotime($date == false ? date('Y-m-d') : $date);
//         $firstDate = date ('Y-m-d', strtotime ('first day of this month', $dt));
//         $lastDate = date ('Y-m-d', strtotime ('last day of this month', $dt));
        
//         return getDateRangeBetweenTwoDates($firstDate, $lastDate);
//     }
// }



// if(!function_exists('isOnLeave')){
//     function isOnLeave($employee, $date){
//        foreach($employee->leave_requests->where('status', ActionStatusEnum::Approved) as $leave){
//             $leaveDate = $leave->dates()->whereDate('date', $date)->first();
//             if($leaveDate){
//                 return true;
//             }            
//         }        
//         return false;
//     }
// }

// if(!function_exists('getLeaveRequestDate')){
//     function getLeaveRequestDate($employee, $date){
//         foreach($employee->leave_requests as $leave){
//             $leaveDate = $leave->dates()->whereDate('date', $date)->first();
//             if($leaveDate){
//                 return $leaveDate;
//             }
            
//        }
//        return null;
//     }
// }

// if(!function_exists('isOnTrip')){
//     function isOnTrip($employee, $date){   
//         if($employee->hasRole('driver')){
//             foreach($employee->vehicles as $vehicle){
//                 $driverDate = $vehicle->dates()->whereDate('date', $date)->first();
//                 if($driverDate){
//                     return true;
//                 }
//             }
//         }else{
//             foreach($employee->trips->where('status', ActionStatusEnum::Approved) as $trip){
//                 $tripDate = $trip->dates()->whereDate('date', $date)->first();
//                 if($tripDate){                
//                     return true;
//                 }
//             }
//         }
//         return false;
//     }
// }

// if(!function_exists('getTripRequestDate')){
//     function getTripRequestDate($employee, $date){
//         if($employee->hasRole('driver')){
//             foreach($employee->vehicles as $vehicle){
//                 $driverDate = $vehicle->dates()->whereDate('date', $date)->first();
//                 if($driverDate){
//                     return $driverDate;
//                 }
//             }
//         }else{
//             foreach($employee->trips->where('status', ActionStatusEnum::Approved) as $trip){
//                 $tripDate = $trip->dates()->whereDate('date', $date)->first();
//                 if($tripDate){
//                     return $tripDate;
//                 }
//            }
//         }
        
//        return null;
//     }
// }



// if(!function_exists('numberToWord')){
//     function numberToWord($num = null)
//     {
//         $num    = ( string ) ( ( int ) $num );
        
//         if( ( int ) ( $num ) && ctype_digit( $num ) )
//         {
//             $words  = array( );
             
//             $num    = str_replace( array( ',' , ' ' ) , '' , trim( $num ) );
             
//             $list1  = array('','one','two','three','four','five','six','seven',
//                 'eight','nine','ten','eleven','twelve','thirteen','fourteen',
//                 'fifteen','sixteen','seventeen','eighteen','nineteen');
             
//             $list2  = array('','ten','twenty','thirty','forty','fifty','sixty',
//                 'seventy','eighty','ninety','hundred');
             
//             $list3  = array('','thousand','million','billion','trillion',
//                 'quadrillion','quintillion','sextillion','septillion',
//                 'octillion','nonillion','decillion','undecillion',
//                 'duodecillion','tredecillion','quattuordecillion',
//                 'quindecillion','sexdecillion','septendecillion',
//                 'octodecillion','novemdecillion','vigintillion');
             
//             $num_length = strlen( $num );
//             $levels = ( int ) ( ( $num_length + 2 ) / 3 );
//             $max_length = $levels * 3;
//             $num    = substr( '00'.$num , -$max_length );
//             $num_levels = str_split( $num , 3 );
             
//             foreach( $num_levels as $num_part )
//             {
//                 $levels--;
//                 $hundreds   = ( int ) ( $num_part / 100 );
//                 $hundreds   = ( $hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ( $hundreds == 1 ? '' : 's' ) . ' ' : '' );
//                 $tens       = ( int ) ( $num_part % 100 );
//                 $singles    = '';
                 
//                 if( $tens < 20 ) { $tens = ( $tens ? ' ' . $list1[$tens] . ' ' : '' ); } else { $tens = ( int ) ( $tens / 10 ); $tens = ' ' . $list2[$tens] . ' '; $singles = ( int ) ( $num_part % 10 ); $singles = ' ' . $list1[$singles] . ' '; } $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_part ) ) ? ' ' . $list3[$levels] . ' ' : '' ); } $commas = count( $words ); if( $commas > 1 )
//             {
//                 $commas = $commas - 1;
//             }
             
//             $words  = implode( ', ' , $words );
             
//             $words  = trim( str_replace( ' ,' , ',' , ucwords( $words ) )  , ', ' );
//             if( $commas )
//             {
//                 $words  = str_replace( ',' , ' and' , $words );
//             }
             
//             return ucfirst($words);
//         }
//         else if( ! ( ( int ) $num ) )
//         {
//             return 'Zero';
//         }
//         return null;
//     }
// }

// if(!function_exists('moneyToWords')){
//     function moneyToWords($num){
//         $ones = array(
//             0 => "ZERO",
//             1 => "ONE",
//             2 => "TWO",
//             3 => "THREE",
//             4 => "FOUR",
//             5 => "FIVE",
//             6 => "SIX",
//             7 => "SEVEN",
//             8 => "EIGHT",
//             9 => "NINE",
//             10 => "TEN",
//             11 => "ELEVEN",
//             12 => "TWELVE",
//             13 => "THIRTEEN",
//             14 => "FOURTEEN",
//             15 => "FIFTEEN",
//             16 => "SIXTEEN",
//             17 => "SEVENTEEN",
//             18 => "EIGHTEEN",
//             19 => "NINETEEN"
//         );
    
//         $tens = array( 
//             0 => "ZERO",
//             1 => "TEN",
//             2 => "TWENTY",
//             3 => "THIRTY", 
//             4 => "FORTY", 
//             5 => "FIFTY", 
//             6 => "SIXTY", 
//             7 => "SEVENTY", 
//             8 => "EIGHTY", 
//             9 => "NINETY" 
//         ); 
    
//         $hundreds = array( 
//             "HUNDRED", 
//             "THOUSAND", 
//             "MILLION", 
//             "BILLION", 
//             "TRILLION", 
//             "QUARDRILLION" 
//         ); /*limit t quadrillion */
    
//         $num = number_format($num ,2,".",","); 
//         $num_arr = explode(".",$num); 
//         $wholenum = $num_arr[0]; 
//         $decnum = $num_arr[1]; 
    
//         $whole_arr = array_reverse(explode(",",$wholenum)); 
//         krsort($whole_arr,1); 
    
//         $rettxt = ""; 
//         foreach($whole_arr as $key => $i){
          
//             while(substr($i,0,1)=="0")
//                 $i=substr($i,1,5);

//             if($i < 20){ 
//                 /* echo "getting:".$i; */
//                 $rettxt .= $ones[$i]; 
//             }elseif($i < 100){ 
//                 if(substr($i,0,1)!="0")  $rettxt .= $tens[substr($i,0,1)]; 
//                 if(substr($i,1,1)!="0") $rettxt .= " ".$ones[substr($i,1,1)]; 
//             } else{ 
//                  if(substr($i,0,1)!="0") $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0]; 
//                 if(substr($i,1,1)!="0")$rettxt .= " ".$tens[substr($i,1,1)]; 
//                 if(substr($i,2,1)!="0")$rettxt .= " ".$ones[substr($i,2,1)]; 
//             } 
//             if($key > 0){ 
//                 $rettxt .= " ".$hundreds[$key]." "; 
//             }
//         } 
        
//         if($rettxt == 'ZERO' || $rettxt == 'ONE'){
//             $rettxt .= " DOLLAR";
//         }else{
//             $rettxt .= " DOLLARS";
//         }

//         if($decnum > 0){
//             $rettxt .= " AND ";
//             if($decnum < 20) {
//                 $rettxt .= $ones[$decnum]." CENTS";
//             } elseif($decnum < 100) {
//                 $rettxt .= $tens[substr($decnum,0,1)];
//                 $rettxt .= " ".$ones[substr($decnum,1,1)]. " CENTS";
//             }
//         }
//         return $rettxt;
//     }
// }

// if(!function_exists('getHour')){
//     function getHour($date, $startTime, $endTime){
//         $to = Carbon::createFromFormat('Y-m-d H:s:i', "{$date} {$endTime}");
//         $from = Carbon::createFromFormat('Y-m-d H:s:i', "{$date} {$startTime}");
//         return $to->diffInHours($from);        
//     }
// }

// if(!function_exists('getTripPeopleinvoled')){
//     function getTripPeopleinvoled($trip) : Collection {
             
//         if(ActionStatusEnum::tryFrom($trip->status->value) == ActionStatusEnum::Cancelled || ActionStatusEnum::tryFrom($trip->status->value) == ActionStatusEnum::Rejected)
//         {            
//             $peopleInvolved = collect();
//             // Employee             
//             $travellers = $trip->employees()->where('id', '!=', $trip->user_id)->get();
//             foreach($travellers as $traveller){
//                 $peopleInvolved->push($traveller->id);
//             }

//             if($trip->vehicles){
//                 // driver
//                 foreach($trip->vehicles as $vehicle){
//                     $peopleInvolved->push($vehicle->driver_id);
//                 }
//             }
            

//             if($trip->advances){
//                 // logistic 
//                 $logistics =  User::where('id', '!=', 1)->permission('prepare_logistic_travel::trip')->get();
//                 foreach($logistics as $logistic){
//                     $peopleInvolved->push($logistic->id);
//                 }
//             }
            

//             if($trip->payments){
//                 // payment
//                 $payments =  User::where('id', '!=', 1)->permission('prepare_payment_travel::trip')->get();
//                 foreach($payments as $payment){
//                     $peopleInvolved->push($payment->id);
//                 }
                
//                 // payment authorize
//                 $authorizes =  User::where('id', '!=', 1)->permission('authorize_payment_travel::trip')->get();
//                 foreach($authorizes as $authorize){
//                     $peopleInvolved->push($authorize->id);
//                 }
//             }
//             return User::whereIn('id', $peopleInvolved->unique())->get();
//         }

//         return [];
//     }
// }

// if(!function_exists('convertHexToRGB'))
// {
//     function convertHexToRGB($hex, $percentage = 1){
//         list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
//         return 'rgba('.$r.', '.$g.', '.$b.', '.$percentage.')';
//     }
// }

// if(!function_exists('getFormattedNumber')){
//     function getFormattedNumber($value, $locale = 'en_US', $style = NumberFormatter::DECIMAL, $precision = 0, $groupingUsed = true, $currencyCode = 'USD') {
//         $formatter = new NumberFormatter($locale, $style);
//         $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
//         $formatter->setAttribute(NumberFormatter::GROUPING_USED, $groupingUsed);
//         if ($style == NumberFormatter::CURRENCY) {
//             $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currencyCode);
//         }
    
//         return $formatter->format($value);
//     }
// }

// if(!function_exists('getIntFromString')){
//     function getIntFromString($value) : int {
//         return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
//     }
// }



// if(!function_exists('getEntitlementBalance')){
//     function getEntitlementBalance($workDurations, $defaultBalance, $startDate, $endDate, $allow_balance_increment, $balance_increment_period, $balance_increment_amount) : int {

//         if($allow_balance_increment == true){
//             if($workDurations <= 1){
//                 return calculateAccrud($defaultBalance, $startDate, $endDate);
//             }else{
//                 $i = 1;
//                 for($x = 1; $x <= intval($workDurations); $x++){
//                     if($i >=  getIntFromString($balance_increment_period)){
//                         $defaultBalance += $balance_increment_amount;
//                         $i = 1; 
//                     }
//                     $i++;
//                 }
//                 return $defaultBalance;
//             }
            
//         }
//         return $defaultBalance;
//     }
// }

// if(!function_exists('generateEntitlement')){
//     function generateEntitlement(Employee $employee, bool $createRecord = false) {
//         if(empty($employee->law)){
//             return false;
//         }

//         $attributes = collect();
//         $jointDate = Carbon::parse($employee->join_date);
//         $workDurations = round(now()->floatDiffInYears($jointDate), 2);
        
//         if($workDurations < 1){
//             $calendarYearStart = Carbon::createFromDate(now()->year, config('setting.calendar_month'), config('setting.calendar_day'), config('app.timezone'));
//             $startDate = $jointDate->toDateString();
//             if(config('setting.entitlement_mode') == 'calendar'){
//                 if($jointDate->lessThanOrEqualTo($calendarYearStart)){
//                     $startDate  = $calendarYearStart->toDateString();
//                     $endDate    = $calendarYearStart->addYear()->subDay()->toDateString();
//                 }else{
//                     $endDate = $calendarYearStart->addYear()->subDay()->toDateString();
//                 }
//             }else{
//                 $endDate = $jointDate->addYear()->subDay()->toDateString();
//             }
//         }else{
//             if(config('setting.entitlement_mode') == 'calendar'){
//                 $startDate  = Carbon::createFromDate(now()->year, config('setting.calendar_month'), config('setting.calendar_day'), config('app.timezone'))->toDateString();
//                 $endDate    = Carbon::parse($startDate)->addYear()->subDay()->toDateString();
//             }else{
//                 $startDate  = Carbon::createFromDate(now()->year, $jointDate->month, $jointDate->day)->toDateString();
//                 $endDate    = Carbon::createFromDate($startDate)->addYear()->subDay()->toDateString();
//             }                        
//         }  
        
//         $leaveTypes = $employee->law->leave_types()->where($employee->gender->value, StatusEnum::Active)->get()->map(function($type){
//             return [
//                 'id'                        => $type->leave_type_id,
//                 'name'                      => $type->leave_type->name,
//                 'balance'                   => $type->balance,
//                 'minimum_request_day'       => $type->minimum_request_day,
//                 'allow_carry'               => $type->allow_carry,
//                 'carry_period'              => $type->carry_period,
//                 'allow_balance_increment'   => !empty($type->balance_increment_period) && !empty($type->balance_increment_amount) ? true : false,
//                 'balance_increment_period'  => $type->balance_increment_period,
//                 'balance_increment_amount'  => $type->balance_increment_amount
//             ];
//         });

//         foreach($leaveTypes as $leaveType){
//             $attributes->push([
//                 'employee_id'   => $employee->id,
//                 'leave_type_id' => $leaveType['id'],
//                 'start_date'    => $startDate,
//                 'end_date'      => $endDate, 
//                 'balance'       => getEntitlementBalance(
//                                         $workDurations, 
//                                         $leaveType['balance'], 
//                                         $startDate, 
//                                         $endDate, 
//                                         $leaveType['allow_balance_increment'], 
//                                         $leaveType['balance_increment_period'], 
//                                         $leaveType['balance_increment_amount']
//                                     ),
//                 'status'        => StatusEnum::Active
//             ]);
//         }

//         if($createRecord == true){                        
//             if(empty($employee->entitlements()->whereDate('end_date', $endDate)->first())){
//                 // reset old entititlement status to suspend
//                 foreach($employee->entitlements() as $entitlement){
//                     $entitlement->update(['status' => StatusEnum::Suspended]);
//                 }
                
//                 // create new entitlements
//                 $employee->entitlements()->createMany($attributes);

//                 return true;
//             }            

//             return false;
//         }

//         return $attributes;        
//     }
// }



// if(!function_exists('checkToilValidDate')){
//     function checkToilValidDate($date, $from, $to): string | null {
//         $date   = Carbon::parse($date);
//         $from   = Carbon::parse($date->toDateString().' '.$from);
//         $to     = Carbon::parse($date->toDateString().' '.$to);
//         $shift  = Auth::user()->shift();  

//         if($date->isBefore(now()) && config('setting.toil_back_date') == false){
//             return __('msg.is_not_allow_back_date');
//         }

//         if($shift->work_days->contains('day', '=', dayName($date))){
//             $workDay = $shift->work_days->where('day', dayName($date))->first();            
//             if($from->isBetween(Carbon::parse($date->toDateString().' '.$workDay->from), Carbon::parse($date->toDateString().' '.$workDay->to)) == true){
//                 return __('msg.is_workday', [
//                     'date'  => $date->toDateString(), 
//                     'name'  => $workDay->day
//                 ]);
//             }
//         }

//         return null;
//     }
// }

// if(!function_exists('getToilExpireDate')){
//     function getToilExpireDate($toilDates){
//         // $toilDates = collect();
//         // foreach($dates as $date){
//         //     if(isWeekend($date)){
//         //         $toilDates->push($date);
//         //     }else{
//         //         if(isPublicHoliday($date)){
//         //             $toilDates->push($date);
//         //         }
//         //     }   
//         // }

//         return Carbon::parse($toilDates->last())->add(config('setting.toil_expire_duration'))->toDateString();
//     }
// }

// if(!function_exists('getToilDates')){
//     function getToilDates($startDate, $endDate) : Collection{
//         $toilDates = collect();
//         $dates = getDateRangeBetweenTwoDates($startDate, $endDate);
//         foreach($dates as $date){
//             if(isWeekend($date)){
//                 $toilDates->push($date);
//             }else{
//                 if(isPublicHoliday($date)){
//                     $toilDates->push($date);
//                 }
//             }
//         }

//         return $toilDates;
//     }
// }

// if(!function_exists('getCarryForwardBalance')){
//     function getCarryForwardBalance($entitlementRemain, $minimumLeaveRequest){
//         if($entitlementRemain > $minimumLeaveRequest){
//             return floatval($entitlementRemain - $minimumLeaveRequest);
//         }
//         return floatval($entitlementRemain);
//     }
// }

// if(!function_exists('getAttendanceRecordDay')){
//     function getAttendanceRecordDay($employee, $from, $to){        
//         return floatval($employee->attendances->whereBetween('date', [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()])->sum('day'));
//     }
// }

// if(!function_exists('viewFullTrip')){
//     function viewFullTrip($progress) : bool {
//         if($progress->value >= 5){
//             return true;
//         }
//         return false;
//     }
// }

// if(!function_exists('getTripPaymentNotification')){
//     function getTripPaymentNotification($paymentItem){
//         if(strtolower($paymentItem->payment_option->name) == 'cash'){
//             return __('msg.trip.payment.cash', [
//                 'request'   => strtolower($paymentItem->payment->trip->trip_type->name),
//                 'location'  => $paymentItem->payment->trip->location,
//                 'from'      => Carbon::parse($paymentItem->payment->trip->departure)->toFormattedDateString(),
//                 'to'        => Carbon::parse($paymentItem->payment->trip->return)->toFormattedDateString()
//             ]);
//         }
//         return __('msg.trip.payment.bank_transfer', [
//             'request'   => strtolower($paymentItem->payment->trip->trip_type->name),
//             'location'  => $paymentItem->payment->trip->location,
//             'from'      => Carbon::parse($paymentItem->payment->trip->departure)->toFormattedDateString(),
//             'to'        => Carbon::parse($paymentItem->payment->trip->return)->toFormattedDateString()
//         ]);
//     }
// }

// if(!function_exists('cambodiaTaxCalculation')){
//     function cambodiaTaxCalculation($salary, $dependents, $taxRate, $taxPercentRates, $taxDepenendentRate) : float {
//         $baseSalary = $salary * $taxRate;        
//         $employeeDependentAmount = $taxDepenendentRate * $dependents;
//         $salaryWithoutTax = $baseSalary - $employeeDependentAmount;
//         $taxPercentRate = getTaxPercentRate($baseSalary, $taxPercentRates);
//         if($taxPercentRate->rate == 0){
//             return 0;
//         }        
//         $taxAmount = floatval((($salaryWithoutTax * convertNumberToPercentage($taxPercentRate->rate)) - $taxPercentRate->deduction_amount) / $taxRate);
//         return round($taxAmount, 2);
//     }
// }

// if(!function_exists('getTaxPercentRate')){
//     function getTaxPercentRate($salary, $taxPercentRates){
//         foreach($taxPercentRates as $percentRate){
//             if(($salary >= $percentRate->min_range && $salary <= $percentRate->max_range) || ($salary >= $percentRate->min_range && empty($percentRate->max_range))){
//                 return $percentRate;
//             }
//         }        
//     }
// }

// if(!function_exists('getSalary')){
//     function getSalary($salary, $percentage){        
//         return round(($salary * convertNumberToPercentage($percentage)), 2);
//     }
// }

// if(!function_exists('convertNumberToPercentage')){
//     function convertNumberToPercentage($num){
//         return $num / 100;
//     }
// }

// if(!function_exists('getWorkingDays')){
//     function getWorkingDays($start_date, $end_date, $holidays = null): int{
//         $start = Carbon::parse($start_date);
//         $end = Carbon::parse($end_date);
        
//         $days = $start->diffInDaysFiltered(function (Carbon $date) use ($holidays) {
//             if(is_null($holidays)){
//                 return $date->isWeekday();
//             }else{
//                 return $date->isWeekday() && !in_array($date, $holidays);
//             }            
//         }, $end);

//         return $days;
//     }
// }