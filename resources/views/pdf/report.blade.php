<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('filament-data-copilot::messages.Analysis Report') }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #334155;
            margin: 0;
            padding: 20px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .header h1 { font-size: 16px; margin: 0 0 5px 0; color: #0f172a; }
        .header h2 { font-size: 14px; margin: 0 0 5px 0; color: #0f172a; }
        .header h3 { font-size: 12px; margin: 0 0 5px 0; color: #0f172a; }
        .header p { font-size: 11px; margin: 0; color: #64748b; }
        .markdown-body p { margin-top: 0; margin-bottom: 8px; font-size: 11px; }
        .markdown-body p:last-child { margin-bottom: 0; }
        .markdown-body strong { font-weight: bold; color: #0f172a; }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 10px;
            text-align: center;
            color: #94a3b8;
        }
        .content {
            margin-bottom: 60px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            font-weight: bold;
            font-size: 11px;
            color: #475569;
            text-transform: uppercase;
        }
        td {
            font-size: 11px;
            color: #334155;
        }
        .chart-container {
            text-align: center;
            margin-top: 20px;
        }
        .chart-container img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    @php
        if (!function_exists('formatReportValue')) {
            function formatReportValue($key, $value, $currency, $locale) {
                if (is_numeric($value)) {
                    $nonCurrencyCols = ['id', 'ano', 'year', 'mes', 'month', 'dia', 'day', 'qtd', 'quantidade', 'quantity', 'count', 'numero', 'number'];
                    foreach ($nonCurrencyCols as $col) {
                        if (stripos($key, $col) !== false) {
                            return $value;
                        }
                    }
                    try {
                        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
                        return $formatter->formatCurrency((float)$value, $currency);
                    } catch (\Exception $e) {
                        return $value;
                    }
                }
                return $value;
            }
        }
    @endphp

    @if(!empty($pluginSettings?->header_html))
        <div class="header">
            {!! $pluginSettings->header_html !!}
        </div>
    @endif

    <div class="content">

        @if($currentOutputMode === 'Gráfico' && !empty($chartBase64))
            <div class="chart-container">
                <img src="{{ $chartBase64 }}" alt="Gráfico da Análise" style="max-height: 400px; width: auto; max-width: 100%;">
            </div>
            
            @if(!empty($chartDescription) || !empty($reportTitle))
                <div style="margin-top: 30px; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    @if(!empty($reportTitle))
                        <h4 style="margin: 0 0 10px 0; font-size: 12px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">{{ $reportTitle }}</h4>
                    @endif
                    @if(!empty($chartDescription))
                        <div class="markdown-body" style="margin: 0; font-size: 11px; color: #334155; line-height: 1.6;">
                            {!! \Illuminate\Support\Str::markdown($chartDescription) !!}
                        </div>
                    @endif
                </div>
            @endif
            
        @elseif(is_array($resultData) && count($resultData) > 0)
            <table>
                <thead>
                    <tr>
                        @foreach($keys as $key)
                            <th>{{ mb_strtoupper(str_replace('_', ' ', $key)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultData as $row)
                        <tr>
                            @foreach($keys as $key)
                                <td>{{ formatReportValue($key, $row[$key], $appCurrency ?? 'BRL', $appLocale ?? 'pt_BR') }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>{{ __('filament-data-copilot::messages.No data returned by the query.') }}</p>
        @endif
    </div>

    @if(!empty($pluginSettings?->footer_html))
        <div class="footer">
            {!! $pluginSettings->footer_html !!}
        </div>
    @endif
</body>
</html>
