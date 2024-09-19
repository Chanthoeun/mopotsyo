<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeaveTypeResource\Pages;
use App\Filament\Admin\Resources\LeaveTypeResource\RelationManagers;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class LeaveTypeResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('model.leave_type');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.leave_types');
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
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('abbr')
                            ->label(__('field.abbr'))
                            ->required()
                            ->unique(ignoreRecord:true)
                            ->maxLength(5),                        
                        Forms\Components\ColorPicker::make('color')
                            ->label(__('field.color'))
                            ->required()
                            ->unique(ignoreRecord:true),
                        Forms\Components\Toggle::make('male')
                            ->label(__('field.male'))
                            ->default(true),
                        Forms\Components\Toggle::make('female')
                            ->label(__('field.female'))
                            ->default(true),
                        Forms\Components\Section::make(__('field.balance'))
                            ->columns(2)
                            ->description(__('desc.balance'))
                            ->schema([
                                Forms\Components\TextInput::make('balance')
                                    ->label(__('field.balance'))
                                    ->required()
                                    ->numeric()
                                    ->helperText(__('helper.balance'))
                                    ->hint(__('hint.day'))
                                    ->placeholder(__('placeholder.balance')),
                                Forms\Components\TextInput::make('maximum_balance')
                                            ->label(__('field.maximum_balance'))
                                            ->numeric()
                                            ->helperText(__('helper.maximum_balance'))
                                            ->hint(__('hint.day')),                                
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('option.minimum_request_days')
                                            ->label(__('field.minimum_request_days'))                                    
                                            ->numeric()
                                            ->helperText(__('helper.minimum_request_day'))
                                            ->hint(__('hint.day'))
                                            ->placeholder(__('placeholder.minimum_request_day')),
                                        Forms\Components\TextInput::make('option.balance_increment_period')
                                            ->label(__('field.balance_increment_period'))
                                            ->helperText(__('helper.balance_increment_period'))
                                            ->placeholder(__('placeholder.balance_increment_period'))
                                            ->hint(__('hint.duration'))
                                            ->live()
                                            ->maxLength(10),
                                        Forms\Components\TextInput::make('option.balance_increment_amount')
                                            ->label(__('field.balance_increment_amount'))
                                            ->numeric()
                                            ->helperText(fn(Get $get) => __('helper.balance_increment_amount', ['period' => $get('balance_increment_period')]))                                            
                                            ->hint(__('hint.day')),
                                        
                                    ])
                            ]),
                        Forms\Components\Group::make()
                            ->columns(1)
                            ->schema([
                                Forms\Components\Section::make(__('field.carry_forward'))
                                    ->description(__('desc.carry_forward'))
                                    ->schema([
                                        Forms\Components\Toggle::make('option.allow_carry_forward')
                                            ->label(__('field.allow_carry_forward'))
                                            ->live(),
                                        Forms\Components\TextInput::make('option.carry_forward_duration')
                                            ->label(__('field.carry_forward_duration'))
                                            ->helperText(__('helper.carry_period'))
                                            ->placeholder(__('placeholder.carry_period'))
                                            ->hint(__('hint.duration'))
                                            ->required()
                                            ->maxLength(10)
                                            ->visible(fn(Get $get) => $get('option.allow_carry_forward') == true),
                                    ])
                            ]),                        
                        Forms\Components\Group::make()
                            ->columns(1)
                            ->schema([
                                Forms\Components\Section::make(__('field.accrual'))
                                    ->description(__('desc.accrued'))
                                    ->schema([
                                        Forms\Components\Toggle::make('allow_accrual')
                                            ->label(__('field.allow_accrual')),
                                    ]),
                            ]),                  
                        
                        Forms\Components\Repeater::make('rules')
                            ->label(__('field.rules'))
                            ->columns(2)
                            ->columnSpanFull()
                            ->collapsed(false)
                            ->addActionLabel(__('btn.label.add', ['label' => __('field.rule')]))
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->reorderable(false)
                            ->schema([                                                                                    
                                Forms\Components\TextInput::make('name')
                                    ->label(__('field.name'))
                                    ->required()
                                    ->unique(ignoreRecord:true)
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                Forms\Components\Select::make('roles')
                                    ->label(__('field.approval_roles'))                                                    
                                    ->required()
                                    ->multiple()
                                    ->options(fn () => Role::whereNot('id', 1)->orderBy('id', 'asc')->get()->pluck('name', 'id')->map(fn ($item) => ucwords(Str::of($item)->replace('_', ' ')))->toArray()),                       
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('from_amount')
                                            ->label(__('field.from_amount'))
                                            ->required()
                                            ->numeric()
                                            ->suffix(__('field.day')),
                                        Forms\Components\TextInput::make('to_amount')
                                            ->label(__('field.to_amount'))
                                            ->required()
                                            ->numeric()
                                            ->suffix(__('field.day')),
                                        Forms\Components\TextInput::make('day_in_advance')
                                            ->label(__('field.day_in_advance'))
                                            ->required()
                                            ->numeric()
                                            ->suffix(__('field.day')),
                                    ]),
                                Forms\Components\ToggleButtons::make('reason')
                                    ->label(__('field.reason'))
                                    ->required()
                                    ->boolean()
                                    ->inline()
                                    ->grouped()
                                    ->default(false),
                                Forms\Components\ToggleButtons::make('attachment')
                                    ->label(__('field.attachment'))
                                    ->required()
                                    ->boolean()
                                    ->inline()
                                    ->default(false)
                                    ->grouped(), 
                                Forms\Components\Textarea::make('description')
                                    ->label(__('field.desc'))
                                    ->columnSpanFull(),                                                                                                                               
                            ]),
                    ])                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([                
                Tables\Columns\ColorColumn::make('color')
                    ->label(__('field.color'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('id')
                    ->label(__('field.id')),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('abbr')
                    ->label(__('field.abbr'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label(__('field.balance'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),                                              
                Tables\Columns\IconColumn::make('male')
                    ->label(__('field.male'))
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('female')
                    ->label(__('field.female'))
                    ->boolean()
                    ->alignCenter(),            
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }
}
