<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PartnerResource\Pages;
use App\Filament\Admin\Resources\PartnerResource\RelationManagers;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PartnerResource extends Resource
{
    use Translatable;

    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'fas-handshake';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('model.partner');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.partners');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.rdf');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('partner_type_id')
                            ->relationship('partnerType', 'name')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('abbr')
                            ->label(__('field.abbr'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(2),
                        Forms\Components\TextInput::make('address')
                            ->label(__('field.address'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('province_id')
                            ->label(__('field.province'))
                            ->relationship('province', 'name', fn(Builder $query) => $query->where('location_type_id', 1))                            
                            ->preload()
                            ->searchable()
                            ->live(onBlur: true),
                        Forms\Components\Select::make('district_id')
                            ->label(__('field.district'))
                            ->relationship('district', 'name', fn(Builder $query, Get $get) => $query->where('parent_id', $get('province_id')))
                            ->preload()
                            ->searchable()
                            ->live(onBlur: true),
                        Forms\Components\Select::make('commune_id')
                            ->label(__('field.commune'))
                            ->relationship('commune', 'name', fn(Builder $query, Get $get) => $query->where('parent_id', $get('district_id')))                            
                            ->preload()
                            ->searchable()
                            ->live(onBlur: true),
                        Forms\Components\Select::make('village_id')
                            ->label(__('field.village'))
                            ->relationship('village', 'name', fn(Builder $query, Get $get) => $query->where('parent_id', $get('commune_id')))                            
                            ->preload()
                            ->searchable(),                                                
                        Forms\Components\TextInput::make('map')
                            ->label(__('field.map'))
                            ->maxLength(255)
                            ->columnSpanFull(),                     
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
                Tables\Columns\TextColumn::make('abbr')
                    ->label(__('field.abbr'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label(__('field.address'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('village.name')
                    ->label(__('field.village'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('commune.name')
                    ->label(__('field.commune'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('district.name')
                    ->label(__('field.district'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('province.name')
                    ->label(__('field.province'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('map')
                    ->label(__('field.map'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),                
                Tables\Columns\ToggleColumn::make('is_sale')
                    ->label(__('field.is_sale')),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('field.is_active')),                
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
                Tables\Filters\SelectFilter::make('provice_id')
                    ->label(__('field.province'))
                    ->relationship('province', 'name', fn(Builder $query) => $query->where('location_type_id', 1))
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('district_id')
                    ->label(__('field.district'))
                    ->relationship('district', 'name', fn(Builder $query) => $query->where('location_type_id', 2))
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('commune_id')
                    ->label(__('field.commune'))
                    ->relationship('commune', 'name', fn(Builder $query) => $query->where('location_type_id', 3))
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('village_id')
                    ->label(__('field.village'))
                    ->relationship('village', 'name', fn(Builder $query) => $query->where('location_type_id', 4))
                    ->preload()
                    ->searchable(),
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
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
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
