<?php

namespace App\Imports;

use App\Enums\GenderEnum;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Settings\SettingWorkingHours;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;

class EmployeeImport implements ToModel, WithHeadingRow, WithChunkReading, WithEvents, ShouldQueue
{
    public $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $empolyee = Employee::updateOrCreate([
            'email' => strtolower(trim($row['email_adress'])),
        ],[
            'employee_id' => $row['employee_id'],
            'name' => [
                'en' => $row['name_latin'],
                'km' => $row['name_khmer'],
            ],
            'nickname'  => [
                'en' => $row['nickname_latin'],
                'km' => $row['nickname_khmer'],
            ],
            'gender' => strtolower($row['sex']) === 'male' ? GenderEnum::MALE : GenderEnum::FEMALE,
            'married'   => strtolower($row['married']) === 'yes' ? true : false, 
            'date_of_birth' => $row['date_of_birth'],
            'nationality'   => $row['nationality'],
            'identity_card_number'   => $row['khmer_id_card'],
            'email' => strtolower(trim($row['email_adress'])),
            'telephone' => collect(explode('/', $row['phone_number']))->map(fn($item) => '+'.$item)->toArray(),
            'address'   => [
                'en' => $row['address_latin'],
                'km' => $row['address_khmer'],
            ],
            'join_date' => $row['employment_date']
        ]);

        // create contract
        
        $empolyee->contracts()->updateOrCreate([
            'position->en'  => $row['position_latin']
        ],[
            'contract_type_id' => ContractType::where('abbr', $row['contract_type'])->first()?->id ?? 1,
            'position' => [
                'en' => $row['position_latin'],
                'km' => $row['position_khmer'],
            ],
            'start_date' => $row['employment_date'],
            'department_id' => 1,
            'shift_id'  => Shift::where('name', $row['working_time'])->first()?->id ?? 1,
        ]);

        // create work schedule
        $workDays = collect(app(SettingWorkingHours::class)->work_days)->map(function($work) use($empolyee){
            return [
                'employee_id'   => $empolyee->id,
                'contract_id'   => $empolyee->contract->id,
                'day_name'      => $work['day_name'],
                'start_time'    => $work['start_time'],
                'end_time'      => $work['end_time'],
                'break_time'    => $work['break_time'],
                'break_from'    => $work['break_from'],
                'break_to'      => $work['break_to'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        })->toArray();
        $empolyee->workDays()->insert($workDays);

        // create user
        User::updateOrCreate([
            'email' => $empolyee->email,
        ],[
            'name' => $empolyee->getTranslations('name'),
            'username' => $empolyee->employee_id,
            'email' => $empolyee->email,
            'password' => bcrypt(Str::password(12)),
            'email_verified_at' => now(),
            'force_renew_password' => true
        ])->assignRole('employee');

        return $empolyee;
    }

    public function chunkSize(): int
    {
        return 20;
    }


    public function registerEvents(): array
    {        
        $user = $this->user;        
        return [   
            BeforeImport::class => function(BeforeImport $event) use($user) {
                $totalRows = collect($event->reader->getTotalRows())->first();                      
                Notification::make()
                    ->title(__('msg.label.in_progress', ['label' => __('btn.import')]))
                    ->body(__('msg.body.in_progress', ['name' => __('action.importing'), 'count' => $totalRows]))
                    ->success()
                    ->icon('fas-clock')
                    ->sendToDatabase($user);
            },    

            AfterImport::class => function(AfterImport $event) use($user) {
                $totalRows = collect($event->reader->getTotalRows())->first();                      
                Notification::make()
                    ->title(__('msg.label.imported', ['label' => __('model.employee')]))
                    ->body(__('msg.body.imported', ['name' => __('model.employee'), 'count' => $totalRows]))
                    ->success()
                    ->icon('fas-check-circle')
                    ->sendToDatabase($user);
                
            },
            ImportFailed::class => function(ImportFailed $event) use($user){
                Notification::make()
                    ->title(__('msg.label.failed', ['label' => __('btn.import')]))
                    ->body(__('msg.body.failed', ['name' => __('model.employee'), 'action' => __('action.importing')]))
                    ->danger()
                    ->icon('fas-circle-xmark')
                    ->sendToDatabase($user);
            },
        ];
    }
}
