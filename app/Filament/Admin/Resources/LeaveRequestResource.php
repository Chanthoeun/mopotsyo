<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LeaveRequestResource\Pages;
use App\Filament\Admin\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('model.leave_request');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.leave_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.employee');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label(__('model.employee'))
                                    ->relationship('user', 'name', function(Builder $query) {
                                        $query->whereHas('employee', function(Builder $query) {
                                            $query->whereNull('resign_date');
                                            $query->whereHas('contracts', function(Builder $query) {
                                                $query->where('is_active', true);
                                                $query->whereHas('contractType', function(Builder $query) {
                                                    $query->where('allow_leave_request', true);
                                                });
                                            });
                                        });
                                    })
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\ToggleButtons::make('leave_type_id')
                                    ->label(__('model.leave_type'))
                                    ->options(function(Get $get) {
                                        if($get('user_id')){
                                            $user = User::with('employee.contracts.contractType')->find($get('user_id'));
                                            $contract = $user->employee->contracts->where('is_active', true)->first();
                                            return LeaveType::whereIn('id', $contract->contractType->leaveTypes)->where($user->employee->gender->value, true)->get()->pluck('abbr', 'id');
                                        }
                                        return LeaveType::all()->pluck('abbr', 'id');
                                    })
                                    ->required()                                    
                                    ->inline()
                                    ->grouped()
                                    ->hint(function($state) {
                                        if($state){
                                            return LeaveType::find($state)->name;
                                        }
                                    })
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\DatePicker::make('from_date')
                                    ->label(__('field.from_date'))
                                    ->placeholder(__('field.select_date'))
                                    ->required()
                                    ->native(false)
                                    ->suffixIcon('fas-calendar'),
                                Forms\Components\DatePicker::make('to_date')
                                ->label(__('field.to_date'))
                                ->placeholder(__('field.select_date'))
                                ->required()
                                ->native(false)
                                ->suffixIcon('fas-calendar'),
                                Forms\Components\Textarea::make('reason')
                                    ->columnSpanFull(),                                                            
                            ])
                    ]),
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([]),
                
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaverequestable_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('leaverequestable_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
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
