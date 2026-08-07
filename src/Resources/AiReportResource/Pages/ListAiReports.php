<?php

namespace Matondojk\FilamentDataCopilot\Resources\AiReportResource\Pages;

use Matondojk\FilamentDataCopilot\Resources\AiReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiReports extends ListRecords
{
    protected static string $resource = AiReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
