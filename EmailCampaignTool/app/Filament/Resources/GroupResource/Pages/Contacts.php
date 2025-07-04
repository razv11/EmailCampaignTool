<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Models\Group;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class Contacts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = GroupResource::class;

    protected static string $view = 'filament.resources.group-resource.pages.contacts';

    public Group $record;

    public function mount(Group $record): void
    {
        $this->record = $record;
    }

    protected function getTableQuery(): Builder
    {
        return $this->record->contacts()->getQuery();
    }

    public function getTitle(): string|Htmlable
    {
        return "Contacts in Group: {$this->record->name}";
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable(),
            TextColumn::make('email'),
            TextColumn::make('phone'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            DeleteAction::make('removeFromGroup')
                ->label('Remove')
                ->requiresConfirmation()
                ->action(function ($record, $livewire) {
                    $livewire->record->contacts()->detach($record->id);

                    Notification::make()
                        ->title('Contact removed from group.')
                        ->success()
                        ->send();
                })
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            DeleteBulkAction::make('bulkRemoveFromGroup')
                ->label('Remove from group')
                ->requiresConfirmation()
                ->action(function ($records, $livewire) {
                    $group = $livewire->record;
                    $group->contacts()->detach($records->pluck('id')->all());

                    Notification::make()
                        ->title('Contacts removed from group.')
                        ->success()
                        ->send();
                })
        ];
    }
}
