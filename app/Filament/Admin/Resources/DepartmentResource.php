<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DepartmentResource\Pages;
use App\Filament\Admin\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DepartmentResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'fas-building-user';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('model.department');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.departments');
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
                    ->label(__('field.name'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->columnSpanFull(), 
                Forms\Components\Select::make('parent_id')
                    ->label(__('field.parent'))
                    ->relationship('parent', 'name')
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('supperior_id')
                    ->label(__('field.supervisor'))
                    ->relationship('supervisor', 'name', fn(Builder $query) => $query->whereHas('employee', fn(Builder $query) => $query->whereNull('resign_date')->orWhereDate('resign_date', '>=', now())))
                    ->preload()
                    ->searchable(),               
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('field.parent'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label(__('field.supervisor'))
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('field.is_active')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDepartments::route('/'),
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
