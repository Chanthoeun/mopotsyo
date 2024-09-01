<?php

namespace App\Filament\Admin\Resources\ShiftResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Reactive;

class ShiftWorkDaysRelationManager extends RelationManager
{
    use Translatable;

    #[Reactive]
    public ?string $activeLocale = null;

    protected static string $relationship = 'shiftWorkDays';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('model.shift_work_days');
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\TextInput::make('day')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('from_time')
                    ->label(__('field.from'))
                    ->required()
                    ->default('08:00:00'),
                Forms\Components\TimePicker::make('to_time')
                    ->label(__('field.to'))
                    ->required()
                    ->default('17:00:00'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day')
            ->columns([
                Tables\Columns\TextColumn::make('day')
                    ->label(__('field.day')),
                Tables\Columns\TextColumn::make('from_time')
                    ->label(__('field.from'))
                    ->time(),
                Tables\Columns\TextColumn::make('to_time')
                    ->label(__('field.to'))
                    ->time(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('btn.label.new', ['label' => __('model.shift_work_day')]))
                    ->color('primary')
                    ->icon('heroicon-o-plus'),
                // Tables\Actions\LocaleSwitcher::make(),
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
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
