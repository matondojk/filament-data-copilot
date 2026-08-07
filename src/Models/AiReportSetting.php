<?php

namespace Matondojk\FilamentDataCopilot\Models;

use Illuminate\Database\Eloquent\Model;

class AiReportSetting extends Model
{
    protected $table = 'ai_reports_settings';

    protected $fillable = [
        'idioma',
        'currency',
        'header_html',
        'footer_html',
        'ai_provider',
        'api_key',
        'allowed_models',
        'business_description',
        'suggestions',
        'model_synonyms',
    ];

    protected $casts = [
        'allowed_models' => 'array',
        'suggestions' => 'array',
        'model_synonyms' => 'array',
    ];
}
