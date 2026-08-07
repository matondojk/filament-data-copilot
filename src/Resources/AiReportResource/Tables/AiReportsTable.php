<?php

namespace Matondojk\FilamentDataCopilot\Resources\AiReportResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class AiReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')
                    ->label(__('filament-data-copilot::messages.Title'))
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('description')
                    ->label(__('filament-data-copilot::messages.Description'))
                    ->limit(30)
                    ->tooltip(function (\Filament\Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    }),
                \Filament\Tables\Columns\TextColumn::make('schedule_email')
                    ->label(__('filament-data-copilot::messages.Recipient Email'))
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('schedule_frequency')
                    ->label(__('filament-data-copilot::messages.Frequency'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => __('filament-data-copilot::messages.Daily'),
                        'weekly' => __('filament-data-copilot::messages.Weekly'),
                        'monthly' => __('filament-data-copilot::messages.Monthly'),
                        default => $state,
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('schedule_time')
                    ->label(__('filament-data-copilot::messages.Time'))
                    ->time('H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label(__('filament-data-copilot::messages.View'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (\Matondojk\FilamentDataCopilot\Models\AiReport $record) => \Matondojk\FilamentDataCopilot\Pages\AiAnalysis::getUrl(['uuid' => $record->uuid])),
                \Filament\Actions\Action::make('send_now')
                    ->label(__('filament-data-copilot::messages.Send Now'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->action(function (\Matondojk\FilamentDataCopilot\Models\AiReport $record) {
                        \Matondojk\FilamentDataCopilot\Jobs\SendImmediateReportEmail::dispatch($record, $record->schedule_email);
                        \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Report queued for sending!'))->success()->send();
                    }),
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
