<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6" style="margin-top: 20px;">
            <x-filament::button type="submit" color="primary">
                <span wire:loading.remove wire:target="save">{{ __('Save Settings') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
