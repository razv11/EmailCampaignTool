<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Models\Email;
use App\Models\Group;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class EditEmailForGroup extends Page
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = GroupResource::class;

    protected static string $view = 'filament.resources.group-resource.pages.edit-email-for-group';

    public ?Group $group = null;
    public ?Email $email = null;
    public ?string $subject = null;
    public ?string $body = null;

    public function mount($record): void {
        $this->group = Group::findOrFail($record);
        $this->email = $this->group->emails()
            ->where('status', 'draft')
            ->latest()
            ->first();

        if($this->email) {
            $this->subject = $this->email->subject;
            $this->body = $this->email->body;
        }
    }

    protected function getFormModel(): Email
    {
        return $this->email ?? new Email();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Edit Email';
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('subject'),
            Forms\Components\RichEditor::make('body')->required(),
        ];
    }

    public function save(): void
    {
        if (!$this->email) {
            $this->email = new Email();
        }

        $data = $this->form->getState();
        $this->email->fill($data);
        $this->email->status = 'draft';
        $this->email->save();

        if (!$this->email->groups()->where('group_id', $this->group->id)->exists()) {
            $this->email->groups()->attach($this->group->id);
        }

        Notification::make()
            ->title('Draft saved successfully.')
            ->success()
            ->send();
    }
}
