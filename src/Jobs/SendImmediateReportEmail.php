<?php

namespace Matondojk\FilamentDataCopilot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Matondojk\FilamentDataCopilot\Models\AiReport;
use Matondojk\FilamentDataCopilot\Models\AiReportSetting;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Matondojk\FilamentDataCopilot\Mail\ScheduledReportMail;

class SendImmediateReportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $report;
    public $email;

    /**
     * Create a new job instance.
     */
    public function __construct(AiReport $report, string $email)
    {
        $this->report = $report;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info("SendImmediateReportEmail Job Started for report: " . $this->report->uuid);
        
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
            'resultData' => $this->report->result_data ?? [],
            'keys' => (!empty($this->report->result_data) && is_array($this->report->result_data)) ? array_keys($this->report->result_data[0]) : [],
            'currentOutputMode' => $this->report->output_mode ?? 'Relatório (Tabela)',
            'chartBase64' => $this->report->getChartBase64(), 
            'reportTitle' => $this->report->title,
            'chartDescription' => $this->report->description,
            'appCurrency' => $pluginSettings->currency ?? 'BRL',
            'appLocale' => app()->getLocale(),
        ]);
        $pdf->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $pdfContent = $pdf->output();

        \Illuminate\Support\Facades\Log::info("PDF Generated. Sending email to: " . $this->email);

        Mail::to($this->email)->send(new ScheduledReportMail($this->report, $pdfContent));
        
        \Illuminate\Support\Facades\Log::info("SendImmediateReportEmail Job Finished.");
    }
}
