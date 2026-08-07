<?php

namespace Matondojk\FilamentDataCopilot\Resources;

use Matondojk\FilamentDataCopilot\Resources\AiReportResource\Pages\CreateAiReport;
use Matondojk\FilamentDataCopilot\Resources\AiReportResource\Pages\EditAiReport;
use Matondojk\FilamentDataCopilot\Resources\AiReportResource\Pages\ListAiReports;
use Matondojk\FilamentDataCopilot\Resources\AiReportResource\Schemas\AiReportForm;
use Matondojk\FilamentDataCopilot\Resources\AiReportResource\Tables\AiReportsTable;
use Matondojk\FilamentDataCopilot\Models\AiReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiReportResource extends Resource
{
    protected static ?string $model = AiReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function getModelLabel(): string
    {
        return __('filament-data-copilot::messages.Scheduled');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-data-copilot::messages.Scheduled');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-data-copilot::messages.Smart Reporting');
    }

    public static function form(Schema $schema): Schema
    {
        return AiReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiReports::route('/'),
            'create' => CreateAiReport::route('/create'),
            'edit' => EditAiReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_scheduled', true);
    }
}
