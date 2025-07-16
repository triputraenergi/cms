<x-filament-panels::page>
    {{-- This will render your filters --}}
    <form wire:submit.prevent="submit" class="mb-6">
        {{ $this->form }}
    </form>

    {{-- This will render your widgets using the correct v3 syntax --}}
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament-panels::page>
