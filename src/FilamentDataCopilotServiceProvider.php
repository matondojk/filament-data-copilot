<?php

namespace Matondojk\FilamentDataCopilot;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Matondojk\FilamentDataCopilot\Pages\AiAnalysis;
use Matondojk\FilamentDataCopilot\Pages\AiSettings;

class FilamentDataCopilotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-data-copilot')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(\Matondojk\FilamentDataCopilot\Commands\SendScheduledReports::class)
            ->hasMigration('create_filament_data_copilot_tables');
    }
}
