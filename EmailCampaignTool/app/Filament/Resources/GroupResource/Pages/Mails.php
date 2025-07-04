<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Models\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class Mails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = GroupResource::class;

    protected static string $view = 'filament.resources.group-resource.pages.mails';
    public Group $record;

    public function mount(Group $record): void
    {
        $this->record = $record;
    }

    protected function getTableQuery(): Builder
    {
        return $this->record->emails()
            ->orderByDesc('created_at')
            ->getQuery();
    }

    public function getTitle(): string|Htmlable
    {
        return "Mails in Group: {$this->record->name}";
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('subject')
                ->wrap()
                ->limit(30)
                ->getStateUsing(fn ($record) => trim($record->subject) !== '' ? $record->subject : '(no subject)'),
            TextColumn::make('status')->sortable(),
            TextColumn::make('sent_at')
                ->sortable(),
            TextColumn::make('sent_count')
                ->label('Recipients')
                ->getStateUsing(fn ($record) => $record->logs()->where('status', 'sent')->count()),
            TextColumn::make('open_rate')
                ->label('Open rate')
                ->getStateUsing(function ($record) {
                    $sentCount = $record->logs()->where('status', 'sent')->count();
                    $openedCount = $record->logs()->whereNotNull('opened_at')->count();

                    return $sentCount > 0
                        ? number_format(($openedCount / $sentCount) * 100, 1) . '%'
                        : '–';
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
                ViewAction::make('preview')
                    ->label('Preview')
                    ->modalHeading('Email details')
                    ->form([
                        Placeholder::make('subject')
                            ->label('Subject')
                            ->content(fn ($record) => $record->subject)
                            ->extraAttributes(['class' => 'break-words whitespace-normal']),

                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn ($record) => $record->status ?? '-'),

                        Placeholder::make('sent_at')
                            ->label('Sent At')
                            ->content(fn ($record) => $record->sent_at ?? '-'),

                        Placeholder::make('body')
                            ->label('Body')
                            ->content(fn ($record) => new HtmlString($record->body))
                            ->extraAttributes(['class' => 'break-words whitespace-normal prose max-w-none']),
                    ]),

            DeleteAction::make('deleteEmail')
                ->before(function ($record) {
                    $record->groups()->detach();
                    $record->delete();
                }),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->before(function ($records) {
                    $records->each(function ($record) {
                        $record->groups()->detach();
                        $record->delete();
                    });
                }),
        ];
    }

    protected function getTableRecordAction(): ?string
    {
        return 'preview';
    }
}
