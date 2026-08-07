<?php

namespace Matondojk\FilamentDataCopilot\Resources\AiReportResource\Pages;

use Matondojk\FilamentDataCopilot\Resources\AiReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiReport extends EditRecord
{
    protected static string $resource = AiReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
