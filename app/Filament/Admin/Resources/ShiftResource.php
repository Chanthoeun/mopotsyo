<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ShiftResource\Pages;
use App\Filament\Admin\Resources\ShiftResource\RelationManagers;
use App\Filament\Admin\Resources\ShiftResource\RelationManagers\ShiftWorkDaysRelationManager;
use App\Models\Shift;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftResource extends Resource
{
    use Translatable;

    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('model.shift');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.shifts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.hr');
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
                            ->unique(ignoreRecord:true)
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function(Set $set){
                                $set("shiftWorkDays.0.day", 'Monday');
                                $set("shiftWorkDays.0.from_time", '08:00:00');
                                $set("shiftWorkDays.0.to_time",'17:00:00');
                                $set("shiftWorkDays.1.day", 'Tuesday');
                                $set("shiftWorkDays.1.from_time", '08:00:00');
                                $set("shiftWorkDays.1.to_time",'17:00:00');
                                $set("shiftWorkDays.2.day", 'Wednesday');
                                $set("shiftWorkDays.2.from_time", '08:00:00');
                                $set("shiftWorkDays.2.to_time",'17:00:00');
                                $set("shiftWorkDays.3.day", 'Thursday');
                                $set("shiftWorkDays.3.from_time", '08:00:00');
                                $set("shiftWorkDays.3.to_time",'17:00:00');
                                $set("shiftWorkDays.4.day", 'Friday');
                                $set("shiftWorkDays.4.from_time", '08:00:00');
                                $set("shiftWorkDays.4.to_time",'17:00:00');
                            }),
                        // Forms\Components\TimePicker::make('start_time')
                        //     ->label(__('field.start_time'))
                        //     ->required()
                        //     ->default('08:00:00'),
                        // Forms\Components\TimePicker::make('end_time')
                        //     ->label(__('field.end_time'))
                        //     ->required()
                        //     ->default('17:00:00'),
                        // Forms\Components\Fieldset::make('break_time')
                        //     ->label(__('field.break_time') . ' (' .__('hint.break_time') . ')')                            
                        //     ->columns(3)
                        //     ->schema([
                        //         Forms\Components\TextInput::make('break_time')
                        //             ->label(__('field.hour'))
                        //             ->required()
                        //             ->numeric()
                        //             ->live()
                        //             ->inputMode('decimal')
                        //             ->default(1)
                        //             ->helperText(fn($state) => __('helper.break_time', ['time' => decimalToTime($state)])),
                        //         Forms\Components\TimePicker::make('break_from')
                        //             ->label(__('field.from'))
                        //             ->required()
                        //             ->default('12:00:00'),
                        //         Forms\Components\TimePicker::make('break_to')
                        //             ->label(__('field.to'))
                        //             ->required()
                        //             ->default('13:00:00'),
                        //     ]),                        
                        // TableRepeater::make('shiftWorkDays')
                        //     ->label(__('model.shift_work_days'))
                        //     ->relationship()                            
                        //     ->reorderable(true)
                        //     ->maxItems(7)         
                        //     ->defaultItems(0)  
                        //     ->headers([
                        //         Header::make(__('field.day')),
                        //         Header::make(__('field.from')),
                        //         Header::make(__('field.to')),
                        //     ])                                             
                        //     ->columnSpan('full')
                        //     ->hiddenOn('edit')
                        //     ->schema([
                        //         Forms\Components\TextInput::make('day')
                        //             ->hiddenLabel()
                        //             ->required()
                        //             ->default('Monday'),
                        //         Forms\Components\TimePicker::make('from_time')
                        //             ->hiddenLabel()
                        //             ->required()
                        //             ->default('08:00:00'),
                        //         Forms\Components\TimePicker::make('to_time')
                        //             ->hiddenLabel()
                        //             ->required()
                        //             ->default('17:00:00'),
                        //     ])
                    ]),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                // Tables\Columns\TextColumn::make('start_time')
                //     ->label(__('field.start_time'))
                //     ->time(),
                // Tables\Columns\TextColumn::make('end_time')
                //     ->label(__('field.end_time'))
                //     ->time(),
                // Tables\Columns\TextColumn::make('break_time')
                //     ->label(__('field.break_time'))
                //     ->numeric()
                //     ->sortable()
                //     ->alignCenter(),
                // Tables\Columns\TextColumn::make('break_from')
                //     ->label(__('field.break_from'))
                //     ->time(),
                // Tables\Columns\TextColumn::make('break_to')
                //     ->label(__('field.break_to'))
                //     ->time(),
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
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
            // ShiftWorkDaysRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
