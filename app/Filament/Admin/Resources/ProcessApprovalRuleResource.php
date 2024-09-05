<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProcessApprovalRuleResource\Pages;
use App\Filament\Admin\Resources\ProcessApprovalRuleResource\RelationManagers;
use App\Models\ProcessApprovalRule;
use EightyNine\Approvals\Services\ModelScannerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class ProcessApprovalRuleResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = ProcessApprovalRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('model.process_approval_rule');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.process_approval_rules');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.admin');
    }

    public static function form(Form $form): Form
    {
        $models = (new ModelScannerService())->getApprovableModels();
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([  
                        Forms\Components\Select::make('feature')
                            ->label(__('field.feature'))
                            ->required()
                            ->live()
                            ->options(function() use ($models) {                                        
                                // remove 'App\Models\' from the value of models
                                $models = array_map(function($model) {
                                    return str_replace('App\Models\\', '', $model);
                                }, $models);
                                
                                return $models;
                            }),                      
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('field.desc'))
                            ->columnSpanFull(),                                              
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('min')
                                    ->label(__('field.min'))
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('max')
                                    ->label(__('field.max'))
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('request_in_advance')
                                    ->label(__('field.request_in_advance'))                                    
                                    ->numeric()
                                    ->default(0),
                            ]),                                            
                        Forms\Components\Toggle::make('require_reason')
                            ->label(__('field.require_reason')),
                        Forms\Components\Toggle::make('require_attachment')
                            ->label(__('field.require_attachment')),
                        Forms\Components\CheckboxList::make('contract_types')
                            ->label(__('model.contract_types'))                                                    
                            ->required()
                            ->options(fn () => \App\Models\ContractType::where('allow_leave_request', true)->orderBy('id')->get()->pluck('name', 'id')->toArray()),
                        Forms\Components\CheckboxList::make('approval_roles')
                            ->label(__('field.approval_roles'))                                                    
                            ->required()
                            ->options(fn () => Role::whereNot('id', 1)->orderBy('id', 'asc')->get()->pluck('name', 'id')->map(fn ($item) => ucwords(Str::of($item)->replace('_', ' ')))->toArray()),                       
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $models = (new ModelScannerService())->getApprovableModels();
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('feature')
                    ->label(__('field.feature'))
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_replace('App\Models\\', '', $state)),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('field.desc'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('min')
                    ->label(__('field.min'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max')
                    ->label(__('field.max'))
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_in_advance')
                    ->label(__('field.request_in_advance'))
                    ->numeric()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => strtolower(trans_choice('field.days_with_count', $state, ['count' => $state])))
                    ->sortable(),
                Tables\Columns\IconColumn::make('require_reason')
                    ->label(__('field.require_reason'))
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('require_attachment')
                    ->label(__('field.require_attachment'))
                    ->boolean()
                    ->alignCenter(),                
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
                Tables\Filters\SelectFilter::make('feature')
                    ->label(__('field.feature'))
                    ->options(function() use ($models) {                                        
                        // remove 'App\Models\' from the value of models
                        $models = array_map(function($model) {
                            return str_replace('App\Models\\', '', $model);
                        }, $models);
                        
                        return $models;
                    }),
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
            'index' => Pages\ListProcessApprovalRules::route('/'),
            'create' => Pages\CreateProcessApprovalRule::route('/create'),
            'edit' => Pages\EditProcessApprovalRule::route('/{record}/edit'),
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
