<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LocationResource\Pages;
use App\Filament\Admin\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationResource extends Resource
{
    use Translatable;

    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'fas-map-location-dot';

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('model.location');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.locations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.admin');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('location_type_id')
                            ->label(__('model.location_type'))
                            ->relationship('locationType', 'name', fn(Builder $query) => $query->where('is_active', true))
                            ->required(),
                        Forms\Components\Select::make('parent_id')
                            ->label(__('field.parent'))
                            ->relationship('parent', 'name')
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('code')
                            ->label(__('field.code'))
                            ->unique(ignoreRecord: true)                            
                            ->maxLength(10),
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('reference')
                            ->label(__('field.reference')),
                        Forms\Components\TextInput::make('note')
                            ->label(__('field.note')),
                    ])
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('field.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('field.parent'))
                    ->numeric()
                    ->sortable(),            
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('field.reference'))                    
                    ->searchable(),            
                Tables\Columns\TextColumn::make('note')
                    ->label(__('field.note'))
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label(__('field.parent'))
                    ->relationship('parent', 'name'),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
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
