<?php

namespace App\Filament\Admin\Resources;

use App\Actions\ApprovalActions;
use App\Filament\Admin\Resources\PurchaseRequestResource\Pages;
use App\Filament\Admin\Resources\PurchaseRequestResource\RelationManagers;
use App\Models\PurchaseRequest;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use EightyNine\Approvals\Tables\Columns\ApprovalStatusColumn;
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
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

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
                ApprovalStatusColumn::make("approvalStatus.status")
                    ->label(__('field.status')),               
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
                Tables\Filters\SelectFilter::make('requested_by')
                    ->label(__('field.requested_by'))
                    ->relationship('user', 'name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions(
                ApprovalActions::make(
                    [                                               
                        Tables\Actions\Action::make('discard') 
                            ->label(__('filament-approvals::approvals.actions.discard'))                           
                            ->visible(fn (Model $record) => (Auth::id() == $record->approvalStatus->creator->id && $record->isApprovalCompleted() && $record->isApproved()))                                                      
                            ->hidden(fn(Model $record) => (Auth::id() != $record->approvalStatus->creator->id || $record->isDiscarded()))                            
                            ->form([
                                Textarea::make('reason')
                                    ->label(__('field.reason'))
                                    ->required()
                            ])
                            ->icon('heroicon-m-archive-box-x-mark')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalIcon('heroicon-m-archive-box-x-mark')
                            ->action(function (array $data, Model $record) {
                                // update status  
                                $record->approvalStatus()->update(['status' => ApprovalStatusEnum::DISCARDED->value]);

                                // update approval status
                                $approval = ProcessApproval::query()->create([
                                    'approvable_type' => $record::getApprovableType(),
                                    'approvable_id' => $record->id,
                                    'process_approval_flow_step_id' => null,
                                    'approval_action' => ApprovalStatusEnum::DISCARDED,
                                    'comment' => $data['reason'],
                                    'user_id' => Auth::id(),
                                    'approver_name' => Auth::user()->full_name1
                                ]);

                                ProcessDiscardedEvent::dispatch($approval);

                                // notification
                                Notification::make()
                                    ->success()
                                    ->icon('fas-user-clock')
                                    ->iconColor('success')
                                    ->title(__('msg.label.discarded', ['label' => __('model.purchase_request')]))
                                    ->send();
                            }),                            
                    ],
                    [                            
                        Tables\Actions\ActionGroup::make([
                            Tables\Actions\EditAction::make(),
                            Tables\Actions\DeleteAction::make(),
                            Tables\Actions\RestoreAction::make(),
                        ])
                    ]
                ));
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
