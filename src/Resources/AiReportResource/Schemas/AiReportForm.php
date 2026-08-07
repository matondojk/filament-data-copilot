<?php

namespace Matondojk\FilamentDataCopilot\Resources\AiReportResource\Schemas;

use Filament\Schemas\Schema;

class AiReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make()
                    ->components([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label(__('filament-data-copilot::messages.Title'))
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('schedule_email')
                            ->label(__('filament-data-copilot::messages.Recipient Email'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\Select::make('schedule_frequency')
                            ->label(__('filament-data-copilot::messages.Frequency'))
                            ->options([
                                'daily' => __('filament-data-copilot::messages.Daily'),
                                'weekly' => __('filament-data-copilot::messages.Weekly'),
                                'monthly' => __('filament-data-copilot::messages.Monthly'),
                            ])
                            ->required(),
                        \Filament\Forms\Components\TimePicker::make('schedule_time')
                            ->label(__('filament-data-copilot::messages.Time'))
                            ->required(),
                    ]),
            ]);
    }
}
