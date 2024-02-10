<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserLimitsRelationManagerResource\RelationManagers\UserLimitsRelationManager;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\PayoutsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\RotationsRelationManager;
use App\Helpers\RoleHelper;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLimit;
use Cknow\Money\Money;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use Filament\Infolists\Components\TextEntry;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationGroup = 'User Management';

    public static function infolist(Infolist $infolist): Infolist
    {
        $user = $infolist->getRecord();
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        TextEntry::make('total_spent')
                            ->label('Total Spent')
                            ->default(fn (): ?string => $user->findSpent()),

                        TextEntry::make('total_returned')
                            ->label('Total Returned')
                            ->default(fn (): ?string => $user->findReturned()),

                        TextEntry::make('total_paid_out')
                            ->label('Total Paid Out')
                            ->default(fn (): ?string => $user->findPaidOut()),

                        TextEntry::make('total_owing')
                            ->label('Total Owing')
                            ->default(fn (): ?string => $user->findOwing()),
                    ])
                    ->columns(4),
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('balance')->money(),
                TextEntry::make('role.name')->label('Role'),
                TextEntry::make('rotations.name')->label('Rotations'),
                TextEntry::make('created_at')->label('Created'),
                TextEntry::make('updated_at')->label('Updated'),
            ]);
    }

    public static function form(Form $form): Form
    {
        $roles = auth()->user()->role->getRolesAvailable()->mapWithKeys(fn ($role) => [$role->id => $role->name]);
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->required()
                    ->hidden(fn ($get): bool => $get('role_id') && !app(RoleHelper::class)->isStaffRole($get('role_id')))
                    ->password(),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Select::make('role_id')
                    ->name('Role')
                    ->options($roles)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($set, $get) {
                        $set('role_id', $get('role_id'));
                    }),
                Select::make('rotation_ids')
                    ->relationship('rotations', 'name')
                    ->required()
                    ->preload()
                    ->multiple(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                // TextColumn::make('email'),
                TextColumn::make('balance')
                    ->money()
                    ->sortable(),
                TextColumn::make('transactions_count')->counts('transactions')->sortable(),
                TextColumn::make('role.name')->label('Role'),
                TextColumn::make('rotations.name')->label('Rotations'),
                TextColumn::make('created_at')->label('Created')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->relationship('role', 'name')
                    ->preload()
                    ->multiple()
                    ->label('Role'),
                SelectFilter::make('rotation_ids')
                    ->relationship('rotations', 'name')
                    ->preload()
                    ->multiple()
                    ->label('Rotations'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            RotationsRelationManager::class,
            UserLimitsRelationManager::class,
            PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}