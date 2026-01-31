<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LogResource\Pages;
use App\Filament\Admin\Resources\LogResource\RelationManagers;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return __('nav.log');
    }

    public static function getModelLabel(): string
    {
        return __('nav.log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('nav.log');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('log_name')
                                    ->label(__('Log Name'))
                                    ->content(fn($record) => $record->log_name),
                                Forms\Components\Placeholder::make('created_at')
                                    ->label(__('Date'))
                                    ->content(fn($record) => $record->created_at?->toDateTimeString()),
                                Forms\Components\Placeholder::make('description')
                                    ->label(__('Description'))
                                    ->content(fn($record) => $record->description)
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('subject')
                                    ->label(__('Subject'))
                                    ->content(fn($record) => $record->subject_type . ' (' . $record->subject_id . ')'),
                                Forms\Components\Placeholder::make('causer')
                                    ->label(__('Causer'))
                                    ->content(fn($record) => $record->causer?->name ?? 'System'),
                            ]),
                        Forms\Components\KeyValue::make('properties')
                            ->label(__('Properties'))
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label(__('Log Name'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Description'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label(__('Subject Type'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label(__('Subject ID'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label(__('Causer'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->options([
                        'Resource' => 'Resource',
                        'Access' => 'Access',
                        'Model' => 'Model',
                        'Notification' => 'Notification',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read only
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
            'index' => Pages\ListLogs::route('/'),
            'view' => Pages\ViewLog::route('/{record}'),
        ];
    }
}
