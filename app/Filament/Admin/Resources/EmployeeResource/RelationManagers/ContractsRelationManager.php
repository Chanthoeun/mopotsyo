<?php

namespace App\Filament\Admin\Resources\EmployeeResource\RelationManagers;

use App\Enums\DayOfWeekEnum;
use App\Models\User;
use App\Settings\SettingWorkingHours;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use EightyNine\Approvals\Services\ModelScannerService;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class ContractsRelationManager extends RelationManager
{
    use Translatable; 

    #[Reactive]
    public ?string $activeLocale = null;

    protected static bool $isLazy = false;

    protected static string $relationship = 'contracts';

    public function form(Form $form): Form
    {
        $models = (new ModelScannerService())->getApprovableModels();
        return $form
            ->schema([
                Wizard::make([ 
                    Wizard\Step::make('contract')
                        ->label(__('model.contract'))
                        ->columns(4)
                        ->schema([
                            Forms\Components\TextInput::make('position')
                                ->label(__('field.position'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('contract_type_id')
                                ->label(__('model.contract_type'))                    
                                ->relationship('contractType', 'name', fn(Builder $query) => $query->orderBy('id', 'asc'))
                                ->required(),
                            Forms\Components\DatePicker::make('start_date')
                                ->label(__('field.start_date'))
                                ->required()
                                ->native(false)
                                ->suffixIcon('fas-calendar'),
                            Forms\Components\DatePicker::make('end_date')
                                ->label(__('field.end_date'))
                                ->native(false)
                                ->suffixIcon('fas-calendar'),
                            Forms\Components\Select::make('department_id')
                                ->label(__('model.departments'))
                                ->relationship('department', 'name')
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('field.name'))
                                        ->required()
                                ]),
                            Forms\Components\Select::make('shift_id')
                                ->label(__('model.shifts'))
                                ->relationship('shift', 'name')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function($state, Set $set){
                                    if($state == 1){
                                        foreach(app(SettingWorkingHours::class)->work_days as $key => $workDay){
                                            $set("employeeWorkDays.{$key}.employee_id", $this->ownerRecord->id);
                                            $set("employeeWorkDays.{$key}.day_name", $workDay['day_name']);
                                            $set("employeeWorkDays.{$key}.start_time", $workDay['start_time']);
                                            $set("employeeWorkDays.{$key}.end_time", $workDay['end_time']);
                                            $set("employeeWorkDays.{$key}.break_time", $workDay['break_time']);
                                            $set("employeeWorkDays.{$key}.break_from", $workDay['break_from']);
                                            $set("employeeWorkDays.{$key}.break_to", $workDay['break_to']);
                                        }                                        
                                    }
                                }),
                            Forms\Components\Select::make('supervisor_id')
                                ->label(__('field.supervisor'))
                                ->relationship('supervisor', 'name', fn(Builder $query) => $query->whereHas('employee', fn(Builder $query) => $query->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->orderBy('id', 'asc'))
                                ->preload()
                                ->searchable(),
                            Forms\Components\Select::make('department_head_id')
                                ->label(__('field.department_head'))
                                ->relationship('departmentHead', 'name', fn(Builder $query) => $query->whereHas('employee', fn(Builder $query) => $query->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->orderBy('id', 'asc'))
                                ->preload()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function($state, Get $get, Set $set){
                                    if($state){
                                        $set('approvers', []);
                                        if($get('supervisor_id')){
                                            $approver = User::find($get('supervisor_id'));
                                            if($get('supervisor_id') == $state){
                                                if($approver->hasRole('acting_director')){
                                                    // Leave Request
                                                    $set("approvers.0.model_type", "App\Models\LeaveRequest");
                                                    $set("approvers.0.role_id", Role::where('name', 'acting_director')->first()->id);
                                                    $set("approvers.0.approver_id", $get('supervisor_id'));
                                                    
                                                    // Overtime
                                                    $set("approvers.1.model_type", "App\Models\OverTime");
                                                    $set("approvers.1.role_id", Role::where('name', 'acting_director')->first()->id);
                                                    $set("approvers.1.approver_id", $get('supervisor_id'));
                                                    
                                                    // Work From Home
                                                    $set("approvers.2.model_type", "App\Models\WorkFromHome");
                                                    $set("approvers.2.role_id", Role::where('name', 'acting_director')->first()->id);
                                                    $set("approvers.2.approver_id", $get('supervisor_id'));
                                                    
                                                    // Purchase Request
                                                    $set("approvers.3.model_type", "App\Models\PurchaseRequest");
                                                    $set("approvers.3.role_id", Role::where('name', 'acting_director')->first()->id);
                                                    $set("approvers.3.approver_id", $get('supervisor_id'));

                                                }else if($approver->hasRole('head_of_department')){
                                                    // Leave Request
                                                    $set("approvers.0.model_type", "App\Models\LeaveRequest");
                                                    $set("approvers.0.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                    $set("approvers.0.approver_id", $state);

                                                    $set("approvers.1.model_type", "App\Models\LeaveRequest");
                                                    $set("approvers.1.role_id", Role::where('name', 'acting_director')->first()->id);
                                                    $set("approvers.1.approver_id", 2);
                                                    
                                                    // Overtime
                                                    $set("approvers.2.model_type", "App\Models\OverTime");
                                                    $set("approvers.2.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                    $set("approvers.2.approver_id", $state);
                                                    
                                                    // Work From Home
                                                    $set("approvers.3.model_type", "App\Models\WorkFromHome");
                                                    $set("approvers.3.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                    $set("approvers.3.approver_id", $state);

                                                    $set("approvers.4.model_type", "App\Models\WorkFromHome");
                                                    $set("approvers.4.role_id", Role::where('name', 'acting_director')->first()->id);
                                                    $set("approvers.4.approver_id", 2);
                                                    
                                                    // Purchase Request
                                                    $set("approvers.5.model_type", "App\Models\PurchaseRequest");
                                                    $set("approvers.5.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                    $set("approvers.5.approver_id", $state);
                                                }
                                                
                                            }else{
                                                // Leave Request
                                                $set("approvers.0.model_type", "App\Models\LeaveRequest");
                                                $set("approvers.0.role_id", Role::where('name', 'supervisor')->first()->id);
                                                $set("approvers.0.approver_id", $get('supervisor_id'));

                                                $set("approvers.1.model_type", "App\Models\LeaveRequest");
                                                $set("approvers.1.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                $set("approvers.1.approver_id", $state);

                                                $set("approvers.2.model_type", "App\Models\LeaveRequest");
                                                $set("approvers.2.role_id", Role::where('name', 'acting_director')->first()->id);
                                                $set("approvers.2.approver_id", 2);
                                                
                                                // Overtime
                                                $set("approvers.3.model_type", "App\Models\OverTime");
                                                $set("approvers.3.role_id", Role::where('name', 'supervisor')->first()->id);
                                                $set("approvers.3.approver_id", $get('supervisor_id'));

                                                $set("approvers.4.model_type", "App\Models\OverTime");
                                                $set("approvers.4.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                $set("approvers.4.approver_id", $state);
                                                
                                                // Work From Home
                                                $set("approvers.5.model_type", "App\Models\WorkFromHome");
                                                $set("approvers.5.role_id", Role::where('name', 'supervisor')->first()->id);
                                                $set("approvers.5.approver_id", $get('supervisor_id'));

                                                $set("approvers.6.model_type", "App\Models\WorkFromHome");
                                                $set("approvers.6.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                $set("approvers.6.approver_id", $state);

                                                $set("approvers.7.model_type", "App\Models\WorkFromHome");
                                                $set("approvers.7.role_id", Role::where('name', 'acting_director')->first()->id);
                                                $set("approvers.7.approver_id", 2);
                                                
                                                // Purchase Request
                                                $set("approvers.8.model_type", "App\Models\PurchaseRequest");
                                                $set("approvers.8.role_id", Role::where('name', 'supervisor')->first()->id);
                                                $set("approvers.8.approver_id", $get('supervisor_id'));

                                                $set("approvers.9.model_type", "App\Models\PurchaseRequest");
                                                $set("approvers.9.role_id", Role::where('name', 'head_of_department')->first()->id);
                                                $set("approvers.9.approver_id", $state);

                                            }
                                        }
                                    }
                                    
                                }),
                            Forms\Components\TextInput::make('contract_no')
                                ->label(__('field.contract_no'))
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\FileUpload::make('file')
                                ->hiddenLabel()
                                ->placeholder(__('field.attachment'))
                                ->directory('employee-contracts')
                                ->acceptedFileTypes(['application/pdf'])
                                ->columnSpan(2),
                            TableRepeater::make('approvers')
                                ->label(__('model.approvers'))
                                ->relationship()                            
                                ->reorderable(true)
                                ->addActionLabel(__('btn.label.add', ['label' => __('model.approver')]))       
                                ->defaultItems(0)                                  
                                ->headers([
                                    Header::make(__('field.feature')),
                                    Header::make(__('model.role')),
                                    Header::make(__('model.approver')),
                                ])                                             
                                ->columnSpan('full')                                
                                ->schema([
                                    Forms\Components\Select::make('model_type')
                                        ->hiddenLabel()
                                        ->options(function() use ($models) {
                                            // remove 'App\Models\' from the value of models
                                            $models = array_map(function($model) {
                                                return str_replace('App\Models\\', '', $model);
                                            }, $models);
                                            return $models;
                                        })
                                        ->required(),
                                    Forms\Components\Select::make('role_id')
                                        ->hiddenLabel()
                                        ->relationship('role', 'name', fn(Builder $query) => $query->whereNot('name', 'super_admin'))
                                        ->getOptionLabelFromRecordUsing(fn (Role $record) => ucwords(Str::of($record->name)->replace('_', ' ')))
                                        ->preload()
                                        ->searchable(),
                                    Forms\Components\Select::make('approver_id')
                                        ->hiddenLabel()
                                        ->relationship('approver', 'name', fn(Builder $query) => $query->whereHas('employee', fn(Builder $query) => $query->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->orderBy('id', 'asc'))
                                        ->preload()
                                        ->searchable(),
                                ]),
                        ]),
                    Wizard\Step::make('workingHours')
                        ->label(__('field.work_info'))
                        ->schema([
                            TableRepeater::make('employeeWorkDays')
                                ->label(__('field.working_hours'))
                                ->relationship()                            
                                ->reorderable(true)
                                ->maxItems(7)  
                                ->addActionLabel(__('btn.label.add', ['label' => __('field.working_hours')]))       
                                ->defaultItems(0)                                  
                                ->headers([
                                    Header::make(__('field.day')),
                                    Header::make(__('field.start_date')),
                                    Header::make(__('field.end_date')),
                                    Header::make(__('field.break_time'))->width('100px'),
                                    Header::make(__('field.from')),
                                    Header::make(__('field.to')),
                                ])                                             
                                ->columnSpan('full')                                
                                ->schema([
                                    Forms\Components\Hidden::make('employee_id')->default($this->ownerRecord->id),
                                    Forms\Components\Select::make('day_name')
                                        ->hiddenLabel()
                                        ->required()
                                        ->options(DayOfWeekEnum::class)
                                        ->default(DayOfWeekEnum::MONDAY)
                                        ->live()
                                        ->afterStateUpdated(function($state, Set $set) {
                                            if($state){
                                                $workDays = app(SettingWorkingHours::class)->work_days;
                                                $workDay = collect($workDays)->where('day_name', is_numeric($state) ? $state : $state->value)->first();   
                                                                                                                                
                                                $set('start_time', $workDay['start_time']);
                                                $set('end_time', $workDay['end_time']);
                                                $set('break_time', $workDay['break_time']);
                                                $set('break_from', $workDay['break_from']);
                                                $set('break_to', $workDay['break_to']);
                                            }
                                        }),
                                    Forms\Components\TimePicker::make('start_time') 
                                        ->hiddenLabel()                                       
                                        ->required(),
                                    Forms\Components\TimePicker::make('end_time')
                                        ->hiddenLabel()
                                        ->required(),
                                    Forms\Components\TextInput::make('break_time')
                                        ->hiddenLabel()
                                        ->required()
                                        ->numeric()
                                        ->live()
                                        ->inputMode('decimal'),
                                    Forms\Components\TimePicker::make('break_from')
                                        ->hiddenLabel()
                                        ->required(),
                                    Forms\Components\TimePicker::make('break_to')
                                        ->hiddenLabel()
                                        ->required(),
                                ]),
                            Placeholder::make('breakTimesPlaceholder')
                                ->hiddenLabel()
                                ->content(fn (): string => __('msg.break_time')) 
                        ]),
                ])
                ->columnSpanFull(),                
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label(__('field.position')),
                Tables\Columns\TextColumn::make('contractType.name')
                    ->label(__('model.contract_type'))
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('field.start_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('field.end_date'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('department.name')
                    ->label(__('model.department')),
                Tables\Columns\TextColumn::make('shift.name')
                    ->label(__('model.shift'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label(__('field.supervisor')),
                Tables\Columns\TextColumn::make('departmentHead.name')
                    ->label(__('field.department_head')),
                Tables\Columns\TextColumn::make('contract_no')
                    ->label(__('field.contract_no'))
                    ->toggleable(isToggledHiddenByDefault: true),                
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('field.is_active')),                
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('btn.label.new', ['label' => __('model.contract')]))
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        if(empty($data['employee_id'])){
                            $data['employee_id'] = $this->ownerRecord->id;
                        }                       
                        return $data;
                    })
                    ->before(function () {
                        $this->ownerRecord->contracts()->update([
                            'is_active' => false,  
                        ]);
                    }),
            ])
            ->actions([
                MediaAction::make('file')
                    ->iconButton()
                    ->icon('fas-file-pdf')
                    ->visible(fn (Model $record) => $record->file)
                    ->media(fn(Model $record) => "/storage/" . $record->file)
                    ->tooltip(__('field.attachment')),
                Tables\Actions\EditAction::make()
                    ->modalWidth('6xl'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
