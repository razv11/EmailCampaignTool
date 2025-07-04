<x-filament::page>
    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <div class="p-4 text-sm text-blue-800 bg-blue-100 border border-blue-200 rounded">
                Tip: You can use placeholders like
                <code>&#123;&#123; name &#125;&#125;</code>,
                <code>&#123;&#123; email &#125;&#125;</code> and
                <code>&#123;&#123; phone &#125;&#125;</code>
                in your email subject or body.
            </div>

            {{ $this->form }}

            <x-filament::button type="submit">
                Save Draft
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
