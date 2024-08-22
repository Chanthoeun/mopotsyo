<?php

namespace App\Filament\Admin\Resources\EmployeeResource\RelationManagers;

use App\Enums\DayOfWeekEnum;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
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

class ContractsRelationManager extends RelationManager
{
    use Translatable; 

    #[Reactive]
    public ?string $activeLocale = null;

    protected static bool $isLazy = false;

    protected static string $relationship = 'contracts';

    public function form(Form $form): Form
    {
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
                                        // Monday
                                        $set('employeeWorkDays.0.employee_id', $this->ownerRecord->id);
                                        $set('employeeWorkDays.0.day_name', DayOfWeekEnum::MONDAY);
                                        $set('employeeWorkDays.0.start_time', '08:00:00');
                                        $set('employeeWorkDays.0.end_time', '17:00:00');
                                        $set('employeeWorkDays.0.break_time', 1);
                                        $set('employeeWorkDays.0.break_from', '12:00:00');
                                        $set('employeeWorkDays.0.break_to', '13:00:00');
                                        // Tuesday
                                        $set('employeeWorkDays.1.employee_id', $this->ownerRecord->id);
                                        $set('employeeWorkDays.1.day_name', DayOfWeekEnum::TUESDAY);
                                        $set('employeeWorkDays.1.start_time', '08:00:00');
                                        $set('employeeWorkDays.1.end_time', '17:00:00');
                                        $set('employeeWorkDays.1.break_time', 1);
                                        $set('employeeWorkDays.1.break_from', '12:00:00');
                                        $set('employeeWorkDays.1.break_to', '13:00:00');
                                        // Wednesday
                                        $set('employeeWorkDays.2.employee_id', $this->ownerRecord->id);
                                        $set('employeeWorkDays.2.day_name', DayOfWeekEnum::WEDNESDAY);
                                        $set('employeeWorkDays.2.start_time', '08:00:00');
                                        $set('employeeWorkDays.2.end_time', '17:00:00');
                                        $set('employeeWorkDays.2.break_time', 1);
                                        $set('employeeWorkDays.2.break_from', '12:00:00');
                                        $set('employeeWorkDays.2.break_to', '13:00:00');
                                        // Thursday
                                        $set('employeeWorkDays.3.employee_id', $this->ownerRecord->id);
                                        $set('employeeWorkDays.3.day_name', DayOfWeekEnum::THURSDAY);
                                        $set('employeeWorkDays.3.start_time', '08:00:00');
                                        $set('employeeWorkDays.3.end_time', '17:00:00');
                                        $set('employeeWorkDays.3.break_time', 1);
                                        $set('employeeWorkDays.3.break_from', '12:00:00');
                                        $set('employeeWorkDays.3.break_to', '13:00:00');
                                        // Friday
                                        $set('employeeWorkDays.4.employee_id', $this->ownerRecord->id);
                                        $set('employeeWorkDays.4.day_name', DayOfWeekEnum::FRIDAY);
                                        $set('employeeWorkDays.4.start_time', '08:00:00');
                                        $set('employeeWorkDays.4.end_time', '17:00:00');
                                        $set('employeeWorkDays.4.break_time', 1);
                                        $set('employeeWorkDays.4.break_from', '12:00:00');
                                        $set('employeeWorkDays.4.break_to', '13:00:00');
                                    }
                                }),
                            Forms\Components\Select::make('supervisor_id')
                                ->label(__('field.supervisor'))
                                ->relationship('supervisor', 'name', fn(Builder $query) => $query->whereNot('id', $this->ownerRecord->id)->whereNull('resign_date')),
                            Forms\Components\TextInput::make('contract_no')
                                ->label(__('field.contract_no'))
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\FileUpload::make('file')
                                ->hiddenLabel()
                                ->placeholder(__('field.attachment'))
                                ->directory('employee-contracts')
                                ->acceptedFileTypes(['application/pdf'])
                                ->columnSpanFull(),
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
                                        ->default(DayOfWeekEnum::MONDAY),
                                    Forms\Components\TimePicker::make('start_time') 
                                        ->hiddenLabel()                                       
                                        ->required()
                                        ->default('08:00:00'),
                                    Forms\Components\TimePicker::make('end_time')
                                        ->hiddenLabel()
                                        ->required()
                                        ->default('17:00:00'),
                                    Forms\Components\TextInput::make('break_time')
                                        ->hiddenLabel()
                                        ->required()
                                        ->numeric()
                                        ->live()
                                        ->inputMode('decimal')
                                        ->default(1),
                                    Forms\Components\TimePicker::make('break_from')
                                        ->hiddenLabel()
                                        ->required()
                                        ->default('12:00:00'),
                                    Forms\Components\TimePicker::make('break_to')
                                        ->hiddenLabel()
                                        ->required()
                                        ->default('13:00:00'),
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
                    ->label(__('model.shift')),
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
