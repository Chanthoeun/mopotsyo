<?php

namespace App\Filament\Admin\Resources;

use App\Enums\Gender;
use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Admin\Resources\UserResource\RelationManagers;
use App\Filament\Admin\Resources\UserResource\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Admin\Resources\UserResource\RelationManagers\ContractsRelationManager;
use App\Filament\Admin\Resources\UserResource\RelationManagers\ProfileRelationManager;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
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
use Livewire\Component;
use Spatie\Permission\Contracts\Role;

class UserResource extends Resource implements HasShieldPermissions
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
                            ->hiddenLabel()
                            ->placeholder(__('field.name'))
                            ->required()
                            ->maxLength(50)
                            ->suffixIcon('fas-language'),
                        Forms\Components\TextInput::make('username')
                            ->hiddenLabel()
                            ->placeholder(__('field.user.username'))
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->hiddenLabel()
                            ->placeholder(__('field.email'))
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Password::make('password')
                            ->hiddenLabel()
                            ->placeholder(__('field.user.password'))
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
                            ->dehydrated(fn ($state) => filled($state)),
                        Forms\Components\Select::make('roles')
                            ->hiddenLabel()
                            ->placeholder(__('model.roles'))
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
                Tables\Columns\ImageColumn::make('employee.photo')
                    ->label(__('field.photo'))
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->visible(fn() => auth()->user()->can('ban_user'))
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
            AuthenticationLogsRelationManager::class,
            ActivityLogsRelationManager::class,
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

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'ban'
        ];
    }
}
