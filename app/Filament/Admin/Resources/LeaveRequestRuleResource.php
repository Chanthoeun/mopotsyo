<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeaveRequestRuleResource\Pages;
use App\Filament\Admin\Resources\LeaveRequestRuleResource\RelationManagers;
use App\Models\ContractType;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class LeaveRequestRuleResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = LeaveRequestRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('model.leave_request_rule');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.leave_request_rules');
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
                        Forms\Components\Select::make('leave_type_id')
                            ->label(__('model.leave_types'))                                                    
                            ->required()
                            ->relationship('leaveType', 'name', fn(Builder $query) => $query->orderBy('id', 'asc')),                                                  
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('field.desc'))
                            ->columnSpanFull(),
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
                            ->grouped(),
                        Forms\Components\ToggleButtons::make('attachment')
                            ->label(__('field.attachment'))
                            ->required()
                            ->boolean()
                            ->inline()
                            ->grouped(),
                                                                
                        Forms\Components\CheckboxList::make('contract_types')
                            ->label(__('model.contract_types'))                                                    
                            ->required()
                            ->options(fn () => \App\Models\ContractType::where('allow_leave_request', true)->orderBy('id')->get()->pluck('name', 'id')->toArray()),
                        Forms\Components\Select::make('role_id')
                            ->label(__('field.approval_role'))
                            ->required()
                            ->relationship('role', 'name', fn(Builder $query) => $query->whereNot('id', 1)->orderBy('id', 'asc'))
                            ->getOptionLabelFromRecordUsing(fn (Role $record) => ucwords(Str::of($record->name)->replace('_', ' ')))
                            ->preload()
                            ->searchable()
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
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label(__('model.leave_types'))
                    ->numeric(),                
                Tables\Columns\TextColumn::make('contract_types')
                    ->label(__('model.contract_types'))
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(1)
                    ->formatStateUsing(fn (string $state): string => ContractType::find($state)->name)
                    ->toggleable(isToggledHiddenByDefault: true),                
                Tables\Columns\TextColumn::make('from_amount')
                    ->label(__('field.from_amount'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('to_amount')
                    ->label(__('field.to_amount'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('day_in_advance')
                    ->label(__('field.day_in_advance'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('reason')
                    ->label(__('field.reason'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('attachment')
                    ->label(__('field.attachment'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role.name')
                    ->label(__('field.approval_role'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ucwords(Str::of($state)->replace('_', ' ')))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('field.created_by'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLeaveRequestRules::route('/'),
            'create' => Pages\CreateLeaveRequestRule::route('/create'),
            'edit' => Pages\EditLeaveRequestRule::route('/{record}/edit'),
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
