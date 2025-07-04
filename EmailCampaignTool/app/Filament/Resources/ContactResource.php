<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact information')->description('Fill in the contact details below.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->minlength(2)
                            ->maxLength(60),
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique('contacts', 'email', ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxlength(20),
                        Forms\Components\Select::make('groups')
                            ->relationship('groups', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextInputColumn::make('phone'),
                Tables\Columns\TextColumn::make('groups.name')
                    ->badge()
                    ->color('primary')
                    ->default('No group assigned')
            ])
            ->filters([
                SelectFilter::make('groups')
                    ->relationship('groups', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('assignToGroups')
                    ->label('Assign to group')
                    ->form(self::groupSelectForm())
                    ->action(function (Contact $record, array $data) {
                        $record->groups()->syncWithoutDetaching($data['group_ids']);
                    })
                    ->color('primary')
                    ->icon('heroicon-o-user-group'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('assignToGroups')
                        ->label('Assign to group')
                        ->form(self::groupSelectForm())
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $contact) {
                                $contact->groups()->syncWithoutDetaching($data['group_ids']);
                            }
                        })
                        ->color('secondary')
                        ->icon('heroicon-o-user-group')
                ])
            ]);
    }

    protected static function groupSelectForm(): array
    {
        return [
            Forms\Components\Select::make('group_ids')
                ->label('Select groups')
                ->required()
                ->multiple()
                ->options(Group::pluck('name', 'id')->toArray())
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return 	'heroicon-o-user';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
