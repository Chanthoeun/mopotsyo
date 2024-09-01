<?php

namespace App\Filament\Admin\Resources;

use App\Enums\GenderEnum;
use App\Filament\Admin\Resources\EmployeeResource\Pages;
use App\Filament\Admin\Resources\EmployeeResource\RelationManagers;
use App\Filament\Admin\Resources\EmployeeResource\RelationManagers\ContractsRelationManager;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;
use Parfaitementweb\FilamentCountryField\Tables\Columns\CountryColumn;
use Rawilk\FilamentPasswordInput\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;
use Illuminate\Support\Str;

class EmployeeResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'fas-id-badge';

    protected static ?int $navigationSort = 7;

    public static function getModelLabel(): string
    {
        return __('model.employee');
    }

    public static function getNavigationLabel(): string
    {
        return __('model.employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('nav.hr');
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
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nickname')
                            ->label(__('field.nickname'))
                            ->maxLength(255),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('employee_id')
                                    ->label(__('field.employee_id'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('fas-id-card')
                                    ->maxLength(20),                        
                                Forms\Components\ToggleButtons::make('gender')
                                    ->label(__('field.gender'))
                                    ->required()
                                    ->options(GenderEnum::class)
                                    ->inline()
                                    ->grouped(),
                                Forms\Components\ToggleButtons::make('married')
                                    ->label(__('field.married'))
                                    ->required()
                                    ->boolean()
                                    ->inline()
                                    ->grouped(),
                            ]),
                        
                        Forms\Components\TextInput::make('email')
                            ->label(__('field.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('fas-envelope')
                            ->maxLength(255),
                        PhoneInput::make('telephone')
                            ->label(__('field.telephone'))
                            ->prefixIcon('fas-phone'),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->label(__('field.date_of_birth'))
                                    ->placeholder(__('field.select_date'))
                                    ->native(false)
                                    ->suffixIcon('fas-calendar'),
                                Forms\Components\DatePicker::make('join_date')
                                    ->label(__('field.join_date'))
                                    ->placeholder(__('field.select_date'))
                                    ->native(false)
                                    ->suffixIcon('fas-calendar'),
                                Country::make('nationality')
                                    ->label(__('field.nationality'))
                                    ->required()
                                    ->searchable()
                                    ->default('KH'),
                            ]),
                        
                        Forms\Components\TextInput::make('address')
                            ->label(__('field.address'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make()
                            ->columns(4)
                            ->schema([
                                Forms\Components\Select::make('province_id')
                                    ->label(__('field.province'))
                                    ->required()
                                    ->relationship('province', 'name', fn(Builder $query) => $query->where('location_type_id', 1))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function(Set $set) {
                                        $set('district_id', null);
                                        $set('commune_id', null);
                                        $set('village_id', null);
                                    }),
                                Forms\Components\Select::make('district_id')
                                    ->label(__('field.district'))
                                    ->relationship('district', 'name', fn(Builder $query, Get $get) => $query->where('location_type_id', 2)->where('parent_id', $get('province_id')))
                                    ->live(onBlur: true)
                                    ->preload()
                                    ->searchable()
                                    ->afterStateUpdated(function(Set $set) {                                        
                                        $set('commune_id', null);
                                        $set('village_id', null);
                                    }),
                                Forms\Components\Select::make('commune_id')
                                    ->label(__('field.commune'))
                                    ->relationship('commune', 'name', fn(Builder $query, Get $get) => $query->where('location_type_id', 3)->where('parent_id', $get('district_id')))                            
                                    ->preload()
                                    ->searchable()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function(Set $set) {
                                        $set('village_id', null);
                                    }),
                                Forms\Components\Select::make('village_id')
                                    ->label(__('field.village'))
                                    ->relationship('village', 'name', fn(Builder $query, Get $get) => $query->where('location_type_id', 4)->where('parent_id', $get('commune_id')))                            
                                    ->preload()
                                    ->searchable(),
                            ]),
                        Forms\Components\FileUpload::make('photo')
                            ->hiddenLabel()
                            ->placeholder(__('field.photo'))
                            ->directory('employee-photos')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,                                                                
                                '1:1',
                            ])
                            ->columnSpanFull(),
                    ]),             
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label(__('field.photo'))
                    ->searchable()
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('employee_id')
                    ->label(__('field.employee_id'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nickname')
                    ->label(__('field.nickname'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label(__('field.gender'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('married')
                    ->label(__('field.married'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label(__('field.date_of_birth'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                CountryColumn::make('nationality')
                    ->label(__('field.nationality'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('field.email'))
                    ->searchable(),
                PhoneColumn::make('telephone')
                    ->label(__('field.telephone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), 
                Tables\Columns\TextColumn::make('join_date')
                    ->label(__('field.join_date'))
                    ->date()
                    ->sortable(), 
                Tables\Columns\TextColumn::make('resign_date')
                    ->label(__('field.resign_date'))
                    ->date()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),                              
                Tables\Columns\IconColumn::make('status')
                    ->label(__('field.status'))
                    ->boolean()
                    ->alignCenter()
                    ->getStateUsing(fn(Model $record) => empty($record->resign_date) && $record->resign_date < Carbon::now() ? true : false),
                Tables\Columns\IconColumn::make('user.name')
                    ->label(__('field.account'))
                    ->boolean()
                    ->alignCenter()
                    ->getStateUsing(fn(Model $record) => empty($record->user) ? false : true),
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('resign')
                        ->label(__('btn.resign'))                        
                        ->icon('fas-person-walking-arrow-right')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('btn.resign'))
                        ->modalDescription(fn(Employee $record) => __('btn.msg.resign', ['name' => $record->name]))
                        ->modalIcon('fas-person-walking-arrow-right')                        
                        ->fillForm(fn(Employee $record) :array => [
                            'resign_date'  => $record->resign_date
                        ])
                        ->form([
                            Forms\Components\DatePicker::make('resign_date')
                                ->label(__('field.resign_date'))
                                ->native(false)
                                ->suffixIcon('fas-calendar-alt')
                        ])
                        ->action(function (array $data, Employee $record) {
                            $record->update([
                                'resign_date' => $data['resign_date']
                            ]);                           

                            // send notification
                            Notification::make()
                                ->title(__('msg.resigned', ['name' => __('field.resign_date')]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('account')
                        ->label(__('btn.label.add', ['label' => __('field.account')]))
                        ->icon('fas-user-plus')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalIcon('fas-user-plus')
                        ->visible(fn(Model $record) => empty($record->user))                                                
                        ->action(function (Employee $record) {
                            $password = Str::password(12); // generate a default password with length of 12 caracters
                            // create login account
                            $user = User::updateOrCreate(
                                [
                                    'email'     => $record->email,                                    
                                ],
                                [
                                    'name'      => $record->getTranslations('name'),
                                    'username'  => $record->employee_id,
                                    'email'     => $record->email, 
                                    'password'  => $password,
                                    'email_verified_at' => now(),
                                    'force_renew_password' => true                          
                                ])->assignRole('employee');
                            
                            // update employee
                            $record->update([
                                'user_id'   => $user->id
                            ]); 
                            
                            // send email notification
                            $user->notify(new WelcomeNotification($password));

                            // send notification
                            Notification::make()
                                ->title(__('msg.added', ['name' => __('field.account')]))
                                ->success()
                                ->send();

                            // Send Email to Employee
                        }),
                    Tables\Actions\Action::make('photo')
                        ->label(__('btn.label.update', ['label' => __('field.photo')]))
                        ->icon('fas-image')
                        ->color('info')                        
                        ->requiresConfirmation()   
                        ->modalIcon('fas-image')
                        ->fillForm(fn(Model $record) :array => [
                            'photo'  => $record->photo
                        ])                     
                        ->form([
                            Forms\Components\FileUpload::make('photo')
                            ->required()
                            ->hiddenLabel()
                            ->directory('employee-photos')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,                                                                
                                '1:1',
                            ]),  
                        ])
                        ->action(function (array $data, Employee $record) {
                            // update profile image
                            $record->update([
                                'photo'  => $data['photo']   
                            ]);                                                    

                            // send notification
                            Notification::make()
                                ->title(__('msg.updated', ['name' => __('field.photo')]))
                                ->success()
                                ->send();                            
                        }),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),                    
                ])
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
            ContractsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
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
