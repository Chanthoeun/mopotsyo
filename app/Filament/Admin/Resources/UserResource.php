<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Rawilk\FilamentPasswordInput\Password;
use Tapp\FilamentAuthenticationLog\RelationManagers\AuthenticationLogsRelationManager;
use Illuminate\Support\Str;
use Spatie\Permission\Contracts\Role;

class UserResource extends Resource
{
    use Translatable; 
    
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getModelLabel(): string
    {
        return __('model.user');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('field.name'))
                            ->required()
                            ->maxLength(50)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('username')
                            ->label(__('field.user.username'))
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->label(__('field.email'))
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Password::make('password')
                            ->label(__('field.user.password'))
                            ->autocomplete(false)
                            ->required(fn (string $context): bool => $context === 'create')
                            ->regeneratePassword(notify: false)
                            ->newPasswordLength(8)
                            ->minLength(8)
                            ->maxLength(20)
                            ->copyable()
                            ->copyMessage(__('field.copied', ['name' => __('field.user.password')]))
                            ->copyMessageDuration(3000)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->columnSpanFull(),
                        Forms\Components\Select::make('roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record) => ucwords(Str::of($record->name)->replace('_', ' ')))
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('field.name'))
                                    ->required()
                                    ->dehydrateStateUsing(fn ($state) => Str::of($state)->lower()->replace(' ', '_'))
                            ])
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label(__('field.user.username'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('field.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('model.roles'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(Str::of($state)->replace('_', ' ')))
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('field.status'))
                    ->alignCenter()
                    ->getStateUsing(function(User $record){
                        return $record->isNotBanned() ? true :false;
                    })
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->alignCenter()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('field.updated_at'))
                    ->alignCenter()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('field.deleted_at'))
                    ->alignCenter()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ban')
                    ->visible(fn(User $record) => $record->hasRole('super_admin'))
                    ->label(fn(User $record) => $record->isNotBanned() ? __('btn.ban') : __('btn.unban') )
                    ->icon(fn(User $record) => $record->isNotBanned() ? 'fas-user-lock' : 'fas-user-check')
                    ->color(fn(User $record) => $record->isNotBanned() ? 'danger' : 'success')                        
                    ->requiresConfirmation()                                                
                    ->modalHeading(fn(User $record) => $record->isNotBanned() ? __('btn.label.ban', ['label' => $record->name]) : __('btn.label.unban', ['label' => $record->name]))
                    ->modalDescription(fn(User $record) => $record->isNotBanned() ? __('btn.msg.ban', ['name' => $record->name]) : __('btn.msg.unban', ['name' => $record->name]))
                    ->modalIcon(fn(User $record) => $record->isNotBanned() ? 'fas-user-lock' : 'fas-user-check')
                    ->modalIconColor(fn(User $record) => $record->isNotBanned() ? 'danger' : 'info')
                    ->action(function (User $record) {              
                        if($record->isNotBanned()){
                            $record->ban();
                        }else{
                            $record->unban();
                        }                           
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuthenticationLogsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
