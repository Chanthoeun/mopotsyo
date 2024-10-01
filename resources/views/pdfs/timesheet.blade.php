<?php 
    use App\Enums\TimesheetTypeEnum;   
    use App\Models\LeaveType;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{__('model.timesheet')}}</title>
    <style type="text/css">
        body{
            margin: 0;
            font-family: 'Khmeros', sans-serif;
            font-size: 12pt;
        }
        @page {
            footer: page-footer;
            margin: 10pt 35pt 10pt 35pt;
            margin-footer: 18pt;
        }

        @page :first {
            margin-top: 5pt;
            margin-left: 35pt;
            margin-right: 35pt;
            margin-bottom: 10pt;
        }
        .page-break {
            page-break-after: always;
        }

        p, li{
            font-size:13px;
        }
        h3{
            font-size:13px;
        }
        .m-0{
            margin: 0px;
        }
        .p-0{
            padding: 0px;
        }
        .pt-5{
            padding-top:5px;
        }
        .px-0{
            padding-left:0px;
            padding-right:0px;
        }
        .px-3{
            padding-left:3px;
            padding-right:3px;
        }
        .px-5{
            padding-left:5px;
            padding-right:5px;
        }
        .py-0{
            padding-top:0px;
            padding-bottom:0px;
        }
        .py-3{
            padding-top:3px;
            padding-bottom:3px;
        }
        .py-5{
            padding-top:5px;
            padding-bottom:5px;
        }
        .mt-10{
            margin-top:10px;
        }
        .mt-5{
            margin-top:5px;
        }

        .mb-5{
            margin-bottom:5px;
        }
        
        .text-center{
            text-align:center !important;
        }
        .text-right{
            text-align:right !important;
        }
        .w-100{
            width: 100%;
        }    
        .w-85{
            width:85%;   
        }
        .w-75{
            width:75%;   
        }
        .w-70{
            width:70%;   
        }
        .w-65{
            width:65%;   
        }
        .w-60{
            width:60%;   
        }
        .w-50{
            width:50%;   
        }
        .w-40{
            width:40%;   
        }
        .w-35{
            width:35%;   
        }
        .w-33{
            width:33%;   
        }
        .w-30{
            width:30%;   
        }
        .w-25{
            width:25%;   
        }
        .w-20{
            width:20%;   
        }
        .w-15{
            width:15%;   
        }
        .w-10{
            width:10%;   
        }
        .logo img{
            width:120px;
            height:120px;
            padding-top:10px;
            /* padding-top:30px; */
        }
        .logo span{
            margin-left:8px;
            top:19px;
            position: absolute;
            font-weight: bold;
            font-size:25px;
        }
        .gray-color{
            color:#5D5D5D;
        }
        .text-bold{
            font-weight: bold;
        }
        .border{
            border:1px solid black;
        }
        table tr,th,td{
            border: 1px solid #d2d2d2;
            border-collapse:collapse;
            padding:1px 8px;
        }
        table tr th{
            background: #F4F4F4;
            font-size:13px;
        }
        table tr td{
            font-size:13px;
        }
        table{
            border-collapse:collapse;
        }
        .box-text{
            margin-top: 0;
            padding-top: 0;
            vertical-align: top;
        }
        .box-text p{
            margin: 0;
            padding: 0;
            line-height: 18px;
        }
        /* .box-text p{
            line-height:10px;
        } */
        .float-left{
            float:left;
        }
        .float-right{
            float: right;
        }
        .total-part{
            font-size:16px;
            line-height:12px;
        }
        .total-right p{
            padding-right:20px;
        }    
    </style>
</head>
<body>
<div class="head-title" style="position:relative;">
    <div class="w-50 float-left">
        <h2 class="m-0 p-0">
            <div class="w-40 logo">
                <img src="{{$logo}}" alt="{{$name}}">                
            </div>
        </h2>      
    </div>
    <div class="w-50 float-right" style="position: absolute; bottom: 0; right: 0;">
        <h2 class="text-right m-0 p-0">{{strtoupper(__('model.timesheet'))}}</h2>
    </div>        
    <div style="clear: both;"></div>    
</div>
<hr>
<div class="table-section bill-tbl w-100">
    <table class="table w-100">
        <tbody>
            <tr>
                <td class="w-25" align="left">{{__('model.department')}}</td>                
                <td colspan="3" align="left">{{$record->user->contract->department->name}}</td>                                                                
            </tr>
            <tr>
                <td class="w-25" align="left">{{__('field.name')}}</td>                
                <td class="w-40" align="left">{{$record->user->full_name}}</td>                                
                <td class="w-20" align="right">{{__('field.year')}}</td>                
                <td class="w-15" align="center">{{$record->to_date->year}}</td>                                
            </tr>
            <tr>
                <td class="w-25" align="left">{{__('field.position')}}</td>                
                <td class="w-40" align="left">{{$record->user->contract->position}}</td>                                
                <td class="w-20" align="right">{{__('field.month')}}</td>                
                <td class="w-15" align="center">{{$record->to_date->monthName}}</td>                                 
            </tr>
            <tr>                               
                <td colspan="3" align="right">Timesheet No</td>                
                <td class="w-15" align="center">{{$record->user->timesheets->count()}}</td>                                 
            </tr>
        </tbody>
    </table>
</div>

<div class="table-section bill-tbl w-100">
    <table class="table w-100">
        <thead>
            <tr>
                <th class="w-20" colspan="2">{{__('field.day')}}</th>                
                <th class="w-20">{{__('field.date')}}</th>                
                <th class="w-15">{{__('field.day')}}</th>
                <th class="w-20">{{__('field.type')}}</th>
                <th>{{__('field.remark')}}</th>                 
            </tr>
        </thead>
        <tbody>
            @foreach ($record->dates as $item)
            <tr>
                <td>{{$item->date->locale('km')->dayName}}</td>                
                <td>{{$item->date->locale('en')->dayName}}</td>                
                <td align="center">{{$item->date->format('d-m-Y')}}</td>                
                <td align="center">{{floatval($item->day)}}</td>
                <td align="center">{{$item->type->getLabel()}}</td>
                <td>{{$item->remark}}</td>                         
            </tr>
            @endforeach
        </tbody>        
    </table>
</div>
<div class="mt-10">
    <div class="w-30  float-left">
        <div class="table-section bill-tbl w-100">
            <table class="table w-100">
                <thead>
                    <tr>
                        <th align="center">@lang('field.activities')</th>
                        <th align="center">@lang('field.no_of_days')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (TimesheetTypeEnum::cases() as $item)
                    @if ($record->dates()->where('type', $item->value)->sum('day') > 0)
                    <tr>
                        <td>{{$item->getLabel()}}</td>
                        <td align="center">{{floatval($record->dates()->where('type', $item->value)->sum('day'))}}</td>
                    </tr>
                    @endif
                    @endforeach
                    <tr>
                        <td align="right">@lang('field.total')</td>
                        <td align="center">{{floatval($record->dates->sum('day'))}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="w-65 float-right">
        <div class="table-section bill-tbl w-100">
            <table class="table w-100">
                <thead>
                    <tr>
                        <th rowspan="2">@lang('model.leave_entitlement')</th>
                        <th rowspan="2">@lang('field.unit')</th>
                        <th rowspan="2">@lang('field.allowance')</th>
                        <th colspan="2">@lang('field.used')</th>
                        <th rowspan="2">@lang('field.remaining')</th>
                    </tr>
                    <tr>
                        <th>@lang('field.all')</th>
                        <th>@lang('field.this_month')</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $user = auth()->user();
                        $leaveTypes = LeaveType::whereIn('id', $user->contract->contractType->leave_types)->where($user->employee->gender->value, true)->orderBy('id', 'asc')->get();
                    @endphp
                    @foreach ($leaveTypes as $item)       
                    @if ($item->balance > 0 || getTakenLeave($user, $item->id, $record->from_date, $record->to_date) > 0)
                    <tr>
                        <td>{{$item->name}}</td>
                        <td align="center">{{__('field.day')}}</td>
                        <td align="center">{{$item->balance}}</td>
                        @if ($item->balance > 0)
                        <td align="center">{{$user->entitlements()->where('leave_type_id', $item->id)->whereDate('end_date', '>=', now())->where('is_active', true)->first()->taken}}</td>
                        <td align="center">{{getTakenLeave($user, $item->id, $record->from_date, $record->to_date)}}</td>
                        <td align="center">{{$user->entitlements()->where('leave_type_id', $item->id)->whereDate('end_date', '>=', now())->where('is_active', true)->first()->remaining}}</td>    
                        @else
                        <td align="center">{{getTakenLeave($user, $item->id)}}</td>
                        <td align="center">{{getTakenLeave($user, $item->id, $record->from_date, $record->to_date)}}</td>
                        <td align="center">0</td>
                        @endif                        
                    </tr>  
                    @endif             
                                                        
                    @endforeach                                                        
                </tbody>
            </table>
        </div>
    </div>        
    <div style="clear: both;"></div>
</div> 
<div class="table-section w-100 mt-10">
    <table class="table w-100">
        <thead>
            <tr>
                <th colspan="2" align="center" class="w-30">@lang('field.submitted_by')</th>
                <th colspan="2" align="center" class="w-40">@lang('field.checked_verified_by')</th>
                <th colspan="2" align="center" class="w-30">@lang('field.approved_by')</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="w-10">@lang('field.signature')</td>    
                <td></td>
                <td class="w-10">@lang('field.signature')</td>    
                <td></td>
                <td class="w-10">@lang('field.signature')</td>    
                <td></td>                
            </tr>                                                        
            <tr>
                <td class="w-10">@lang('field.name')</td>    
                <td>{{$user->full_name}}</td>
                <td class="w-10">@lang('field.name')</td>    
                <td></td>
                <td class="w-10">@lang('field.name')</td>    
                <td></td>                
            </tr>                                                        
            <tr>
                <td class="w-10">@lang('field.position')</td>    
                <td>{{$user->contract->position}}</td>
                <td class="w-10">@lang('field.position')</td>    
                <td></td>
                <td class="w-10">@lang('field.position')</td>    
                <td></td>                
            </tr>                                                        
            <tr>
                <td class="w-10">@lang('field.date')</td>    
                <td></td>
                <td class="w-10">@lang('field.date')</td>    
                <td></td>
                <td class="w-10">@lang('field.date')</td>    
                <td></td>                
            </tr>                                                        
        </tbody>
    </table>
</div>
</body>
</html>
