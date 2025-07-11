<x-filament::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($this->getWidgets() as $widget)
            @livewire($widget, [], key($widget))
        @endforeach
    </div>
</x-filament::page>
