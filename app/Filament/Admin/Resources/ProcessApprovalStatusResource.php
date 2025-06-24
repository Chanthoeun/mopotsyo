<?php

namespace App\Filament\Admin\Resources;

use App\Enums\ApprovalStatuEnum;
use App\Enums\ProcessApprovalStatuEnum;
use App\Filament\Admin\Resources\ProcessApprovalStatusResource\Pages;
use App\Filament\Admin\Resources\ProcessApprovalStatusResource\RelationManagers;
use App\Models\ProcessApprovalStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProcessApprovalStatusResource extends Resource
{
    protected static ?string $model = ProcessApprovalStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 7;


    public static function getModelLabel(): string
    {
        return __('model.process_approval_status');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.process_approval_statuses');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('approvable_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('approvable_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('steps')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(10)
                    ->default('Created'),
                Forms\Components\Select::make('creator_id')
                    ->relationship('creator', 'name'),
                Forms\Components\TextInput::make('tenant_id')
                    ->maxLength(38),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('field.requested_by'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvable.leaveType.name')
                    ->label(__('model.leave_type'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvable.from_date')
                    ->label(__('field.from_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('approvable.to_date')
                    ->label(__('field.to_date'))
                    ->date(),
                Tables\Columns\TextColumn::make('approvable.days')
                    ->label(__('field.day'))
                    ->alignCenter(),
                Tables\Columns\SelectColumn::make('status')
                    ->label(__('field.status'))
                    ->options(ProcessApprovalStatuEnum::class),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProcessApprovalStatuses::route('/'),
            'create' => Pages\CreateProcessApprovalStatus::route('/create'),
            'edit' => Pages\EditProcessApprovalStatus::route('/{record}/edit'),
        ];
    }
}
