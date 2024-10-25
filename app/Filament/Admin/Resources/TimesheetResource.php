<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TimesheetTypeEnum;
use App\Filament\Admin\Resources\TimesheetResource\Pages;
use App\Filament\Admin\Resources\TimesheetResource\RelationManagers;
use App\Models\Timesheet;
use App\Settings\SettingGeneral;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class TimesheetResource extends Resource
{
    protected static ?string $model = Timesheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return __('model.timesheet');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.timesheets');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.employee');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('from_date')
                            ->label(__('field.from_date'))
                            ->placeholder(__('field.select_date'))
                            ->required()
                            ->native(false)
                            ->suffixIcon('fas-calendar')
                            ->closeOnDateSelection()
                            ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.from_date" />')))
                            ->live()
                            ->afterStateUpdated(function($state, Set $set, Get $get) {
                                $set('to_date', $state);
                                $set('dates', []);
                                if($get('to_date')){
                                    // get user
                                    $user = Auth::user();
                                    $i = 0;
                                    $dates = getDateRangeBetweenTwoDates($state, $get('to_date'));
                                    foreach($dates as $date){
                                        // check if date is public holiday
                                        $holiday = publicHoliday($date);
                                        $weekend = weekend($date);
                                        $leave = isOnLeave($user, $date);
                                        $overtime = isOvertime($user, $date);
                                        $workFromHome = isWorkFromHome($user, $date);
                                        $workDay = isWorkDay($user, $date);
                                        $switchWorkDayFromDate = isSwitchWorkDay($user, $date);
                                        $switchWorkDayToDate = isSwitchWorkDayToDate($user, $date);
                                        if($holiday){
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", 1);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::HOLIDAY);
                                            $set("dates.{$i}.remark", $holiday->name);
                                            $i++;
                                        }else if($leave){
                                            if($leave->day < 1){
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", $leave->day);
                                                $set("dates.{$i}.type", TimesheetTypeEnum::LEAVE);
                                                $set("dates.{$i}.remark", $leave->requestdateable->leaveType->name);
                                                $i++;

                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", floatval(1 - $leave->day));
                                                $set("dates.{$i}.type", TimesheetTypeEnum::LEAVE);
                                                $set("dates.{$i}.remark", $leave->requestdateable->leaveType->name);
                                                $i++;
                                            }else{
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", $leave->day);
                                                $set("dates.{$i}.type", TimesheetTypeEnum::LEAVE);
                                                $set("dates.{$i}.remark", $leave->requestdateable->leaveType->name);
                                                $i++;
                                            }  
                                        }else if($overtime){
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", $overtime->day);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::OVERTIME);
                                            $set("dates.{$i}.remark", $overtime->requestdateable->reason);
                                            $i++;                                                                                                  
                                        }else if($weekend){                                            
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", 1);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::WEEKEND);
                                            $i++;
                                        }else if($workFromHome){
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", $workFromHome->day);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::HOME);
                                            $set("dates.{$i}.remark", $workFromHome->requestdateable->reason);
                                            $i++;
                                        }else if($workDay){   
                                            if($switchWorkDayFromDate){
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::NOT_WORK);   
                                                $set("dates.{$i}.remark", __('msg.body.switch_working_date', ['from' => $switchWorkDayFromDate->from_date->toDateString(), 'to' => $switchWorkDayFromDate->to_date->toDateString(), 'reason' => $switchWorkDayFromDate->reason]));
                                            }else{
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::OFFICE);   
                                            }                                             
                                            $i++;                                    
                                        }else{
                                            if($switchWorkDayToDate){
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::OFFICE);   
                                                $set("dates.{$i}.remark", __('msg.body.switch_working_date', ['from' => $switchWorkDayFromDate->from_date->toDateString(), 'to' => $switchWorkDayFromDate->to_date->toDateString(), 'reason' => $switchWorkDayFromDate->reason]));
                                            }else{
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::NOT_WORK);   
                                            }                                             
                                            $i++;                                    
                                        }
                                    }
                                }
                            }),
                        Forms\Components\DatePicker::make('to_date')
                            ->label(__('field.to_date'))
                            ->placeholder(__('field.select_date'))
                            ->required()
                            ->native(false)
                            ->suffixIcon('fas-calendar')
                            ->closeOnDateSelection()
                            ->hint(new HtmlString(Blade::render('<x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="data.to_date" />')))
                            ->live()
                            ->afterStateUpdated(function($state, Set $set, Get $get) {                                
                                $set('dates', []);
                                if($get('from_date')){
                                    // get user
                                    $user = Auth::user();
                                    $i = 0;
                                    $dates = getDateRangeBetweenTwoDates($get('from_date'), $state);
                                    foreach($dates as $date){                                        
                                        $holiday = publicHoliday($date);
                                        $weekend = weekend($date);
                                        $leave = isOnLeave($user, $date);
                                        $overtime = isOvertime($user, $date);
                                        $workFromHome = isWorkFromHome($user, $date);
                                        $workDay = isWorkDay($user, $date);
                                        $switchWorkDayFromDate = isSwitchWorkDay($user, $date);
                                        $switchWorkDayToDate = isSwitchWorkDayToDate($user, $date);
                                        if($holiday){
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", 1);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::HOLIDAY);
                                            $set("dates.{$i}.remark", $holiday->name);
                                            $i++;
                                        }else if($leave){
                                            if($leave->day < 1){
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", $leave->day);
                                                $set("dates.{$i}.type", TimesheetTypeEnum::LEAVE);
                                                $set("dates.{$i}.remark", $leave->requestdateable->leaveType->name);
                                                $i++;

                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", floatval(1 - $leave->day));
                                                $set("dates.{$i}.type", TimesheetTypeEnum::LEAVE);
                                                $set("dates.{$i}.remark", $leave->requestdateable->leaveType->name);
                                                $i++;
                                            }else{
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", $leave->day);
                                                $set("dates.{$i}.type", TimesheetTypeEnum::LEAVE);
                                                $set("dates.{$i}.remark", $leave->requestdateable->leaveType->name);
                                                $i++;
                                            }  
                                        }else if($overtime){
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", $overtime->day);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::OVERTIME);
                                            $set("dates.{$i}.remark", $overtime->requestdateable->reason);
                                            $i++;                                                                                                  
                                        }else if($weekend){                                            
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", 1);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::WEEKEND);
                                            $i++;
                                        }else if($workFromHome){
                                            $set("dates.{$i}.date", $date->toDateString());
                                            $set("dates.{$i}.day", $workFromHome->day);
                                            $set("dates.{$i}.type", TimesheetTypeEnum::HOME);
                                            $set("dates.{$i}.remark", $workFromHome->requestdateable->reason);
                                            $i++;
                                        }else if($workDay){   
                                            if($switchWorkDayFromDate){
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::NOT_WORK);   
                                                $set("dates.{$i}.remark", __('msg.body.switch_working_date', ['from' => $switchWorkDayFromDate->from_date->toDateString(), 'to' => $switchWorkDayFromDate->to_date->toDateString(), 'reason' => $switchWorkDayFromDate->reason]));
                                            }else{
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::OFFICE);   
                                            }                                             
                                            $i++;                                    
                                        }else{
                                            if($switchWorkDayToDate){
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::OFFICE);   
                                                $set("dates.{$i}.remark", __('msg.body.switch_working_date', ['from' => $switchWorkDayFromDate->from_date->toDateString(), 'to' => $switchWorkDayFromDate->to_date->toDateString(), 'reason' => $switchWorkDayFromDate->reason]));
                                            }else{
                                                $set("dates.{$i}.date", $date->toDateString());
                                                $set("dates.{$i}.day", 1);    
                                                $set("dates.{$i}.type", TimesheetTypeEnum::NOT_WORK);   
                                            }                                             
                                            $i++;                                    
                                        }
                                    }
                                }
                            }),
                        TableRepeater::make('dates')
                            ->label(__('model.timesheet_dates'))
                            ->relationship()
                            ->required()       
                            ->addable(false)                                                                  
                            ->deletable(false)
                            ->defaultItems(0)   
                            ->live()                           
                            ->columnSpanFull()
                            ->headers([
                                Header::make(__('field.date'))->width('220px'),
                                Header::make(__('field.day'))->width('150px'),
                                Header::make(__('field.type'))->width('200px'),
                                Header::make(__('field.remark')),
                            ])
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->hiddenLabel()
                                    ->placeholder(__('field.select_date'))
                                    ->required()
                                    ->native(false)
                                    ->readOnly()
                                    ->suffixIcon('fas-calendar'),                              
                                Forms\Components\TextInput::make('day')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->numeric()
                                    ->default(0.00),
                                Forms\Components\Select::make('type')
                                    ->hiddenLabel()                                            
                                    ->required()
                                    ->options(TimesheetTypeEnum::class),
                                Forms\Components\TextInput::make('remark')
                                    ->hiddenLabel(),
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_date')
                    ->label(__('field.from_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->label(__('field.to_date'))
                    ->date()
                    ->sortable(),                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('field.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('field.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('downoad')
                    ->label(__('btn.download'))                    
                    ->icon('fas-file-pdf')
                    ->color('success')
                    ->action(function (Model $record) {
                        $pdf = PDF::loadView('pdfs.timesheet', [                            
                            'type'      => __('model.timesheet'),                        
                            'logo'      => 'data:image/png;base64, '.base64_encode(file_get_contents('storage/'.app(SettingGeneral::class)->logo)),                            
                            'name'      => $record->name,
                            'record'    => $record
                        ]);                        
                        
                        Notification::make()
                            ->title(__('msg.downloaded', ['name' => __('model.timesheet')]))
                            ->success()
                            ->send();

                        return response()->streamDownload(function () use ($pdf) { echo $pdf->stream(); }, $record->name.'.pdf');
                    }),                
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
            'create' => Pages\CreateTimesheet::route('/create'),
            'edit' => Pages\EditTimesheet::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('user_id', Auth::id());
    }
}
