<?php

namespace Matondojk\FilamentDataCopilot\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Matondojk\FilamentDataCopilot\Models\AiReport;
use Matondojk\FilamentDataCopilot\Models\AiReportSetting;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Matondojk\FilamentDataCopilot\Mail\ScheduledReportMail;

#[Signature('ai:send-scheduled-reports')]
#[Description('Executa o envio dos relatórios de IA agendados via e-mail')]
class SendScheduledReports extends Command
{
    public function handle()
    {
        $this->info(__('filament-data-copilot::messages.Starting scheduled reports check...'));
        $now = now();
        $reports = AiReport::where('is_scheduled', true)
                           ->whereNotNull('schedule_email')
                           ->get();

        $count = 0;
        foreach ($reports as $report) {
            if ($report->schedule_time) {
                $scheduledHour = \Carbon\Carbon::parse($report->schedule_time)->hour;
                if ($scheduledHour !== $now->hour) continue;
            }

            if ($report->schedule_frequency === 'weekly' && $now->dayOfWeek !== \Carbon\Carbon::MONDAY) {
                continue;
            }
            if ($report->schedule_frequency === 'monthly' && $now->day !== 1) {
                continue;
            }

            $this->info(__('filament-data-copilot::messages.Generating PDF for report UUID: ') . $report->uuid . __('filament-data-copilot::messages. and sending to ') . $report->schedule_email);
            
            $pluginSettings = AiReportSetting::first();
            $replacements = [
                '#date' => now()->format('d/m/Y'),
                '#datetime' => now()->format('d/m/Y H:i:s'),
                '#appname' => config('app.name'),
                '#appurl' => config('app.url'),
            ];
            
            if ($pluginSettings) {
                $pluginSettings->header_html = strtr($pluginSettings->header_html ?? '', $replacements);
                $pluginSettings->footer_html = strtr($pluginSettings->footer_html ?? '', $replacements);
            }

            $pdf = Pdf::loadView('filament-data-copilot::pdf.report', [
                'pluginSettings' => $pluginSettings,
                'resultData' => $report->result_data ?? [],
                'keys' => (!empty($report->result_data) && is_array($report->result_data)) ? array_keys($report->result_data[0]) : [],
                'currentOutputMode' => $report->output_mode ?? 'Relatório (Tabela)',
                'chartBase64' => $report->getChartBase64(), 
                'reportTitle' => $report->title,
                'chartDescription' => $report->description,
                'appCurrency' => $pluginSettings->currency ?? 'BRL',
                'appLocale' => app()->getLocale(),
            ]);
            $pdf->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
            $pdfContent = $pdf->output();

            Mail::to($report->schedule_email)->send(new ScheduledReportMail($report, $pdfContent));
            $count++;
        }
        
        $this->info(__('filament-data-copilot::messages.Completed. ') . $count . __('filament-data-copilot::messages. reports sent.'));
    }
}
