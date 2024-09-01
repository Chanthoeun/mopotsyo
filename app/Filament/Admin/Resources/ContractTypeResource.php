<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ContractTypeResource\Pages;
use App\Filament\Admin\Resources\ContractTypeResource\RelationManagers;
use App\Models\ContractType;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
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

class ContractTypeResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = ContractType::class;

    protected static ?string $navigationIcon = 'fas-file-signature';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('model.contract_type');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.contract_types');
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
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('abbr')
                            ->label(__('field.abbr'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(5),
                        Forms\Components\ToggleButtons::make('allow_leave_request')
                            ->label(__('field.allow_leave_request'))
                            ->required()
                            ->boolean()
                            ->inline()
                            ->grouped()
                            ->live(),
                        Forms\Components\CheckboxList::make('leave_types')
                            ->label(__('model.leave_type'))
                            ->options(fn () => \App\Models\LeaveType::all()->pluck('name', 'id')->toArray())
                            ->required(fn(Get $get) => $get('allow_leave_request'))                            
                            ->columns(3)
                            ->columnSpanFull(),
                        
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
                Tables\Columns\TextColumn::make('abbr') 
                    ->label(__('field.abbr'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('allow_leave_request')
                    ->label(__('field.allow_leave_request'))
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
            'index' => Pages\ListContractTypes::route('/'),
            'create' => Pages\CreateContractType::route('/create'),
            'edit' => Pages\EditContractType::route('/{record}/edit'),
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
