<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Enums\Gender;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\Concerns\Translatable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\Reactive;

class ProfileRelationManager extends RelationManager
{
    use Translatable;

    #[Reactive]
    public ?string $activeLocale = null;
    
    protected static string $relationship = 'profile';

    public function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->hiddenLabel()
                            ->placeholder(__('field.photo'))
                            ->directory('employee-photos')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,                                                                
                                '1:1',
                            ]),
                    ]),
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Forms\Components\ToggleButtons::make('gender')
                            ->label(__('field.gender'))
                            ->hiddenLabel()
                            ->options(Gender::class)
                            ->inline()
                            ->required(),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->hiddenLabel()
                            ->placeholder(__('field.date_of_birth'))
                            ->required()
                            ->native(false),
                    ]),
                Forms\Components\Group::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('telephone')
                            ->hiddenLabel()
                            ->placeholder(__('field.telephone'))
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->hiddenLabel()
                            ->placeholder(__('field.address'))
                            ->required()
                            ->suffixIcon('fas-language'),
                        Forms\Components\TextInput::make('position')
                            ->hiddenLabel()
                            ->placeholder(__('field.position'))
                            ->required()
                            ->suffixIcon('fas-language'),
                        Forms\Components\Select::make('department_id')
                            ->hiddenLabel()
                            ->placeholder(__('model.departments'))
                            ->relationship('department', 'name')
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('field.name'))
                                    ->required()
                            ]),
                        Forms\Components\Select::make('shift_id')
                            ->hiddenLabel()
                            ->placeholder(__('model.shifts'))
                            ->relationship('shift', 'name')
                            ->required(),
                        Forms\Components\Select::make('supervisor_id')
                            ->hiddenLabel()
                            ->placeholder(__('field.supervisor'))
                            ->relationship('supervisor', 'name', fn(Builder $query) => $query->whereNot('id', 1)),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('position')
            ->columns([                
                Tables\Columns\TextColumn::make('gender')
                    ->label(__('field.gender')),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label(__('field.date_of_birth'))
                    ->date(),
                Tables\Columns\TextColumn::make('position'),
                Tables\Columns\TextColumn::make('telephone'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('btn.add'))
                    ->color('primary')
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => empty($this->getOwnerRecord()->profile)),                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),                
            ])            
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
