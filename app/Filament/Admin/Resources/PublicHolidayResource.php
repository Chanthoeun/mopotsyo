<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PublicHolidayResource\Pages;
use App\Filament\Admin\Resources\PublicHolidayResource\RelationManagers;
use App\Models\PublicHoliday;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PublicHolidayResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = PublicHoliday::class;

    protected static ?string $navigationIcon = 'fas-users';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('model.public_holiday');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.public_holidays');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.hr');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, Closure $fail) use($get) {
                                $holiday = PublicHoliday::where('name->en', $get('name'))->where('date', $value)->first();
                                if($holiday){
                                    $fail(__('msg.already_exist'));
                                }                                
                            };
                        },
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
                Tables\Columns\TextColumn::make('date')
                    ->label(__('field.date'))
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPublicHolidays::route('/'),
            'create' => Pages\CreatePublicHoliday::route('/create'),
            'edit' => Pages\EditPublicHoliday::route('/{record}/edit'),
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
