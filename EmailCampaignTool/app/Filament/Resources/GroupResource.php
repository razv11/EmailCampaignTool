<?php

namespace App\Filament\Resources;

use App\EmailStatus;
use App\Filament\Resources\GroupResource\Pages;
use App\Models\EmailLog;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group information')->description('Fill in the group details below.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->minlength(2)
                            ->maxLength(60),
                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->minlength(2)
                            ->maxLength(100),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('contacts_count')
                    ->sortable()
                    ->label('Contacts')
                    ->getStateUsing(fn($record) => $record->contacts()->count())
                    ->colors([
                        'danger' => fn($state) => $state === 0,
                        'success' => fn($state) => $state > 0,
                    ])
                    ->formatStateUsing(fn($state) => $state === 0 ? 'No contacts' : "$state contact(s)"),
                Tables\Columns\TextColumn::make('mail_count')
                    ->label('Sent Mails')
                    ->getStateUsing(fn($record) => $record->emails()->where('status', EmailStatus::Sent)->count()),
            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('showContacts')
                    ->label('Contacts')
                    ->url(fn($record) => route('filament.admin.resources.groups.contacts', ['record' => $record]))
                    ->color('secondary')
                    ->icon('heroicon-o-users'),

                Tables\Actions\Action::make('showMails')
                    ->label('Mails')
                    ->url(fn($record) => route('filament.admin.resources.groups.mails', ['record' => $record]))
                    ->color('secondary')
                    ->icon('heroicon-o-inbox'),

                Tables\Actions\Action::make('editMail')
                    ->label('Draft Email')
                    ->url(fn($record) => route('filament.admin.resources.groups.edit-mail', ['record' => $record]))
                    ->color('primary')
                    ->icon('heroicon-o-pencil'),

                Tables\Actions\Action::make('sendGroupMail')
                    ->label('Send Mail')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $hasContacts = $record->contacts()->count() > 0;
                        if (!$hasContacts) {
                            Notification::make()
                                ->title('No contacts found.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $email = $record->emails()->where('status', EmailStatus::Draft)->latest()->first();
                        if (!$email) {
                            Notification::make()
                                ->title('No draft found.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $contacts = $record->contacts;
                        foreach ($contacts as $contact) {
                            $log = EmailLog::create([
                                'email_id' => $email->id,
                                'contact_id' => $contact->id,
                                'status' => 'pending',
                            ]);

                            $bodyWithTracking = $email->body ;

                            $replacements = [
                                'name' => $contact->name,
                                'email' => $contact->email,
                                'phone' => $contact->phone
                            ];

                            $bodyWithTracking = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($replacements) {
                                $key = $matches[1];
                                return $replacements[$key] ?? '';
                            }, $bodyWithTracking);

                            $personalizedSubject = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) use ($replacements) {
                                $key = $matches[1];
                                return $replacements[$key] ?? '';
                            }, $email->subject);

                            $bodyWithTracking .= '<img src="' . route('email.track.open', [$log->id]) . '" width="1" height="1" style="display:block;" alt="">';

                            Mail::html($bodyWithTracking, function ($message) use ($personalizedSubject, $contact) {
                                $message->to($contact->email)
                                    ->subject($personalizedSubject);
                            });

                            $log->update(['status' => 'sent']);
                        }

                        $email->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Email sent to all contacts.')
                            ->success()
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return 	'heroicon-o-user-group';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
            'contacts' => Pages\Contacts::route('/{record}/contacts'),
            'mails' => Pages\Mails::route('/{record}/mails'),
            'edit-mail' => Pages\EditEmailForGroup::route('/{record}/edit-mail'),
        ];
    }
}
