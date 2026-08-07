<?php

namespace Matondojk\FilamentDataCopilot;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Matondojk\FilamentDataCopilot\Pages\AiAnalysis;
use Matondojk\FilamentDataCopilot\Pages\AiSettings;

class FilamentDataCopilotPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-data-copilot';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                AiAnalysis::class,
                AiSettings::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
