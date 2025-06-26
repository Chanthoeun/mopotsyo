<?php

namespace App\Filament\Admin\Resources\LeaveCarryForwardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

class RequestDatesRelationManager extends RelationManager
{
    protected static string $relationship = 'requestDates';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('date')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('field.date'))
                    ->date()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('field.from_time'))
                    ->time()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('field.to_time'))
                    ->time()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('day')
                    ->label(__('field.day'))
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('sync')
                    ->label(__('btn.label.sync', ['label' => __('model.leave_request')]))
                    ->requiresConfirmation()
                    ->icon('fas-sync')
                    ->action(function(){                        
                        $from_date = $this->ownerRecord->start_date;
                        $to_date = $this->ownerRecord->end_date;
                        $user = $this->ownerRecord->user;
                        $leaves = $user->leaveRequests()->with('requestDates')->where('leave_type_id', 1)
                        ->whereHas('requestDates', function($q) use($from_date, $to_date){
                                $q->whereBetween('date', [$from_date, $to_date]);  
                        })->whereHas('approvalStatus', static function ($q) {
                            return $q->whereIn('status', [ApprovalStatusEnum::APPROVED->value, ApprovalStatusEnum::PENDING->value, ApprovalStatusEnum::SUBMITTED->value]);
                        })->get();
                        
                        foreach($leaves as $leave){
                            $requestDates = $leave->requestDates()->whereBetween('date', [$from_date, $to_date])->get();
                            foreach($requestDates as $requestDate){ 
                                $requestDate->leave_carry_forward_id = $this->ownerRecord->id;
                                $requestDate->save();
                            }
                        }
                    }),

            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
