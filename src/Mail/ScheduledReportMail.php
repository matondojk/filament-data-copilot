<?php

namespace Matondojk\FilamentDataCopilot\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $report;
    public $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct($report, $pdfContent)
    {
        $this->report = $report;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('filament-data-copilot::messages.Intelligent Report: ') . ($this->report->title ?? __('filament-data-copilot::messages.Data Analysis')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'filament-data-copilot::emails.scheduled_report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $this->pdfContent, __('filament-data-copilot::messages.Report-').$this->report->uuid.'.pdf')
                    ->withMime('application/pdf'),
        ];
    }
}
