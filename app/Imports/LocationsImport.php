<?php

namespace App\Imports;

use App\Models\Location;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;

class LocationsImport implements ToModel, WithHeadingRow, WithChunkReading, ShouldQueue, WithEvents
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
        // parent                 
        $parentCode = substr($row['code'], 0, (strlen($row['code']) - 2));
        return Location::updateOrCreate([
            'code' => $row['code'],
        ],[
            'parent_id' =>  !empty($parentCode) ? Location::where('code', $parentCode)->first()?->id : null,
            'location_type_id' => match(strlen($row['code'])) {
                                        2 => 1,     
                                        4 => 2,
                                        6 => 3,
                                        8 => 4,
            },
            'code' => $row['code'],
            'name' => ['en' => $row['english'], 'km' => $row['khmer']],
            'reference' => $row['reference'],
            'note' => $row['note'],
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
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
                    ->title(__('msg.label.imported', ['label' => __('model.location')]))
                    ->body(__('msg.body.imported', ['name' => __('model.location'), 'count' => $totalRows]))
                    ->success()
                    ->icon('fas-check-circle')
                    ->sendToDatabase($user);
                
            },
            ImportFailed::class => function(ImportFailed $event) use($user){
                Notification::make()
                    ->title(__('msg.label.failed', ['label' => __('btn.import')]))
                    ->body(__('msg.body.failed', ['name' => __('model.location'), 'action' => __('action.importing')]))
                    ->danger()
                    ->icon('fas-circle-xmark')
                    ->sendToDatabase($user);
            },
        ];
    }

}