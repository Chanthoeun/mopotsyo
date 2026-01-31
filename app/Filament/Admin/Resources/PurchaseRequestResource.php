<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\PurchaseRequestResource\Pages;
use App\Filament\Admin\Resources\PurchaseRequestResource\RelationManagers;
use App\Models\PurchaseRequest;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('model.purchase_request');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.purchase_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.procurement');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(3)
                    ->schema([
                        Forms\Components\Group::make()
                            ->columnSpan(['lg' => 2])
                            ->schema([
                                Forms\Components\TextInput::make('for')
                                    ->label(__('field.for_project_department'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('location')
                                    ->label(__('field.for_location'))
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Group::make()
                            ->columnSpan(['lg' => 1])
                            ->schema([
                                Forms\Components\TextInput::make('pr_no')
                                    ->label(__('field.pr_no'))
                                    ->default(generatePrNo()),
                                Forms\Components\DatePicker::make('expected_date')
                                    ->label(__('field.expected_date'))
                                    ->required()
                                    ->native(false)
                                    ->suffixIcon('fas-calendar')
                                    ->closeOnDateSelection(),
                            ]),
                        Forms\Components\Textarea::make('purpose')
                            ->label(__('field.purpose'))
                            ->required()
                            ->columnSpanFull(),
                        TableRepeater::make('requestItems')
                            ->label(__('field.request_items'))
                            ->relationship()
                            ->required()
                            ->defaultItems(1)
                            ->addActionLabel(__('btn.label.add', ['label' => __('field.item')]))
                            ->columnSpanFull()
                            ->headers([
                                Header::make(__('field.desc')),
                                Header::make(__('field.unit'))->width('100px'),
                                Header::make(__('field.remark'))->width('250px'),
                            ])
                            ->schema([
                                Forms\Components\Textarea::make('name')
                                    ->hiddenLabel()
                                    ->required()
                                    ->rows(1)
                                    ->autosize(),
                                Forms\Components\TextInput::make('unit')
                                    ->hiddenLabel()
                                    ->required()
                                    ->numeric(),
                                Forms\Components\TextInput::make('remark')
                                    ->hiddenLabel(),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requested')
                    ->label(__('field.requested_by'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('pr_no')
                    ->label(__('field.pr_no'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('for')
                    ->label(__('field.for_project_department'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label(__('field.for_location'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('use_funds')
                    ->label(__('field.use_funds'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expected_date')
                    ->label(__('field.expected_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_approver_name')
                    ->label(__('field.current_approver'))
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge(),
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make()->visible(fn() => Auth::user()->hasRole('super_admin')),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('field.status'))
                    ->options(\App\Enums\Status::class),
            ])
            ->actions(
                array_merge(
                    [
                        Tables\Actions\Action::make('submit')
                            ->label(__('btn.submit'))
                            ->icon('heroicon-o-paper-airplane')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->action(function (PurchaseRequest $record) {
                                $record->submitToApproval();
                                Notification::make()
                                    ->title(__('msg.body.submitted', ['label' => __('model.purchase_request')]))
                                    ->success()
                                    ->send();
                            })
                            ->visible(fn(PurchaseRequest $record) => $record->status === \App\Enums\Status::CREATED),
                    ],
                    ApprovalActions::make(
                        [
                            Tables\Actions\ActionGroup::make([
                                Tables\Actions\ViewAction::make(),
                                Tables\Actions\EditAction::make(),
                            ])
                        ]
                    )
                )
            );
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
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'view' => Pages\ViewPurchaseRequest::route('/{record}'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user.employee.contracts.supervisor', 'requestItems', 'approvalSteps.approver']);

        if (!Auth::user()->hasRole(['super_admin', 'human_resource'])) {
            $query->where(function (Builder $query) {
                $query->where('user_id', Auth::id())
                    ->orWhereHas('approvalSteps', fn($q) => $q->where('approver_id', Auth::id()));
            });
        }

        return $query;
    }
}
