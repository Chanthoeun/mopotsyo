<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('nav.quick_actions') }}
        </x-slot>

        <div class="flex flex-wrap gap-3">
            @foreach ($this->getActions() as $action)
                <x-filament::button :href="$action['url']" :color="$action['color']" :icon="$action['icon']" tag="a">
                    {{ $action['label'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>