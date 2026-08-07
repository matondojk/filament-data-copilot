<?php

namespace Matondojk\FilamentDataCopilot\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Matondojk\FilamentDataCopilot\Models\AiReportSetting;
use Matondojk\FilamentDataCopilot\Models\AiReport;
use Livewire\Attributes\Url;
use Illuminate\Support\Str;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;

class AiAnalysis extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $title = null;
    protected static ?string $slug = 'ai/analysis';
    protected string $view = 'filament-data-copilot::pages.ai-analysis';

    public function getTitle(): string
    {
        return __('filament-data-copilot::messages.Data Copilot');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-data-copilot::messages.Data Copilot');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-data-copilot::messages.Smart Reporting');
    }

    #[Url]
    public ?string $uuid = null;

    public ?array $data = [];

    public function scheduleReportAction(): Action
    {
        return Action::make('scheduleReport')
            ->label(__('filament-data-copilot::messages.Schedule'))
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->form([
                \Filament\Forms\Components\Select::make('schedule_frequency')
                    ->label(__('filament-data-copilot::messages.Frequency'))
                    ->options([
                        'now' => __('filament-data-copilot::messages.Now'),
                        'daily' => __('filament-data-copilot::messages.Daily'),
                        'weekly' => __('filament-data-copilot::messages.Weekly'),
                        'monthly' => __('filament-data-copilot::messages.Monthly'),
                    ])
                    ->reactive()
                    ->required(),
                \Filament\Forms\Components\TimePicker::make('schedule_time')
                    ->label(__('filament-data-copilot::messages.Time'))
                    ->hidden(fn ($get) => $get('schedule_frequency') === 'now')
                    ->required(fn ($get) => $get('schedule_frequency') !== 'now'),
                \Filament\Forms\Components\TextInput::make('schedule_email')
                    ->label(__('filament-data-copilot::messages.Email'))
                    ->email()
                    ->required()
                    ->default(auth()->user()->email ?? ''),
            ])
            ->action(function (array $data) {
                if (!$this->uuid) {
                    \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Generate a report first.'))->warning()->send();
                    return;
                }
                $report = AiReport::where('uuid', $this->uuid)->first();
                if ($report) {
                    $report->update([
                        'output_mode' => $this->currentOutputMode,
                        'chart_type' => $this->currentChartType,
                    ]);
                    
                    if ($data['schedule_frequency'] === 'now') {
                        // Queue immediate sending
                        \App\Jobs\SendImmediateReportEmail::dispatch($report, $data['schedule_email']);
                        \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Report queued for sending!'))->success()->send();
                    } else {
                        // Schedule
                        $report->update([
                            'is_scheduled' => true,
                            'schedule_frequency' => $data['schedule_frequency'],
                            'schedule_time' => $data['schedule_time'],
                            'schedule_email' => $data['schedule_email'],
                        ]);
                        \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Schedule saved successfully!'))->success()->send();
                    }
                }
            });
    }

    public function mount(): void
    {
        if ($this->uuid) {
            $report = AiReport::where('uuid', $this->uuid)->first();
            if ($report) {
                $this->resultData = $report->result_data;
                $this->reportTitle = $report->title;
                $this->chartDescription = $report->description;
                $this->currentOutputMode = $report->output_mode;
                $this->currentChartType = $report->chart_type;
                
                $this->form->fill([
                    'command' => $report->command,
                    'output_mode' => $report->output_mode,
                    'chart_type' => $report->chart_type,
                ]);
                return;
            } else {
                $this->uuid = null;
            }
        }
        
        $this->form->fill([
            'output_mode' => 'Gráfico',
            'chart_type' => 'bar'
        ]);
    }

    private function getAiSuggestions(): array
    {
        $settings = AiReportSetting::first();
        
        if ($settings && !empty($settings->suggestions) && is_array($settings->suggestions)) {
            $suggestions = [];
            foreach ($settings->suggestions as $suggestion) {
                if (isset($suggestion['prompt']) && isset($suggestion['title'])) {
                    $suggestions[$suggestion['prompt']] = $suggestion['title'];
                }
            }
            if (!empty($suggestions)) {
                return $suggestions;
            }
        }
        
        $businessDesc = $settings->business_description ?? 'Nenhuma descrição de negócio fornecida.';
        $allowedModels = empty($settings->allowed_models) ? 'Nenhum modelo específico' : implode(', ', $settings->allowed_models);
        
        // Retorna um array no formato ['prompt_completo' => 'Título da Sugestão']
        return [
            "Analise os dados considerando o negócio: {$businessDesc}. Foco nas tabelas: {$allowedModels}. Gere um relatório detalhado de crescimento." => 'Relatório de Crescimento (IA)',
            "Crie um resumo financeiro e de fluxo de usuários para o modelo de negócio: {$businessDesc}." => 'Resumo Financeiro (IA)',
            "Identifique anomalias nos registros recentes do banco de dados para os modelos: {$allowedModels}." => 'Busca de Anomalias (IA)',
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('filament-data-copilot::messages.Instructions'))
                    ->description(__('filament-data-copilot::messages.Select a suggestion or type your own analysis command.'))
                    ->icon('heroicon-o-command-line')
                    ->schema([
                        Select::make('analysis_suggestion')
                            ->label(__('filament-data-copilot::messages.Analysis Suggestions'))
                            ->options($this->getAiSuggestions())
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('command', $state)),
        
                        Textarea::make('command')
                            ->label(__('filament-data-copilot::messages.Analysis Command (or SQL)'))
                            ->required()
                            ->rows(4),
                    ]),
                    
                Section::make(__('filament-data-copilot::messages.Filters & Options'))
                    ->description(__('filament-data-copilot::messages.Configure how the results should be displayed.'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Grid::make(2)->schema([
                            Radio::make('output_mode')
                                ->label(__('filament-data-copilot::messages.Initial Output Mode'))
                                ->options([
                                    'Gráfico' => __('filament-data-copilot::messages.Chart'),
                                    'Relatório (Tabela)' => __('filament-data-copilot::messages.Report (Table)'),
                                ])
                                ->default('Gráfico')
                                ->live()
                                ->afterStateUpdated(fn ($state, $livewire) => $livewire->currentOutputMode = $state),
        
                            Select::make('chart_type')
                                ->label(__('filament-data-copilot::messages.Chart Type'))
                                ->options([
                                    'bar' => __('filament-data-copilot::messages.Bar'),
                                    'line' => __('filament-data-copilot::messages.Line'),
                                    'pie' => __('filament-data-copilot::messages.Pie'),
                                    'doughnut' => __('filament-data-copilot::messages.Doughnut'),
                                    'stacked' => __('filament-data-copilot::messages.Stacked Bar'),
                                    'multi-axis' => __('filament-data-copilot::messages.Multi-Axis Line'),
                                    'polarArea' => __('filament-data-copilot::messages.Polar Area'),
                                    'combo' => __('filament-data-copilot::messages.Combo (Bar + Line)'),
                                ])
                                ->default('bar')
                                ->live()
                                ->afterStateUpdated(fn ($state, $livewire) => $livewire->currentChartType = $state)
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('output_mode') === 'Gráfico'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public ?array $resultData = null;
    public ?string $generatedSql = null;
    public ?string $analysisError = null;
    public string $currentOutputMode = 'Gráfico';
    public ?string $currentChartType = 'bar';
    public ?string $reportTitle = null;
    public ?string $chartDescription = null;
    public string $appCurrency = 'BRL';
    public string $appLocale = 'pt_BR';

    public function submit(): void
    {
        $this->resultData = null;
        $this->generatedSql = null;
        $this->analysisError = null;
        $this->reportTitle = null;
        $this->chartDescription = null;
        
        $data = $this->form->getState();
        $settings = AiReportSetting::first();
        
        $this->appCurrency = $settings->currency ?? 'BRL';
        $this->appLocale = $settings->idioma ?? config('app.locale', 'pt_BR');
        
        $this->currentOutputMode = $data['output_mode'] ?? 'Gráfico';
        $this->currentChartType = $data['chart_type'] ?? 'bar';
        
        if (!$settings || empty($settings->allowed_models)) {
            \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Warning'))->body(__('filament-data-copilot::messages.Configure allowed models first.'))->warning()->send();
            return;
        }

        $command = $data['command'];
        $synonyms = $settings->model_synonyms ?? [];
        $allowedModels = $settings->allowed_models;
        
        $matchedTables = [];
        $lowerCommand = mb_strtolower($command);
        
        foreach ($synonyms as $table => $terms) {
            $tableMatched = false;
            if (str_contains($lowerCommand, mb_strtolower($table))) {
                $tableMatched = true;
            } else {
                foreach ($terms as $term) {
                    if (str_contains($lowerCommand, mb_strtolower($term))) {
                        $tableMatched = true;
                        break;
                    }
                }
            }
            if ($tableMatched) {
                $matchedTables[] = $table;
            }
        }
        
        $schemaStrings = [];
        $allowedTableNames = [];
        
        if (!empty($allowedModels) && is_array($allowedModels)) {
            foreach ($allowedModels as $modelClass) {
                if (class_exists($modelClass)) {
                    try {
                        $instance = new $modelClass;
                        $table = $instance->getTable();
                        $allowedTableNames[] = $table;
                        
                        if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
                            $schemaStrings[] = "- Tabela `$table` (Model: " . class_basename($modelClass) . "): " . implode(', ', $columns);
                        }
                    } catch (\Exception $e) {
                        // Continue on error for a specific model
                    }
                }
            }
        }
        
        $schemaContext = implode("\n", $schemaStrings);
        $provider = $settings->ai_provider ?? config('ai.default');
        $idiomaStr = $settings->idioma ?? config('app.locale', 'pt_BR');

        if (!empty($provider) && !empty($settings->api_key)) {
            config(["ai.providers.{$provider}.key" => $settings->api_key]);
            config(['ai.default' => $provider]);
        }
        
        $textModel = ($provider === 'openai') ? 'gpt-4o-mini' : null;
        $sqlModel = ($provider === 'openai') ? 'gpt-4o' : null;

        try {
            $agent = new \Laravel\Ai\AnonymousAgent(
                "You are an expert SQL database assistant. The user's language is '{$idiomaStr}'. Convert natural language instructions into perfectly valid MySQL/MariaDB SQL queries. IMPORTANT SQL RULES: You must comply with ONLY_FULL_GROUP_BY. If you use GROUP BY, every unaggregated column in the SELECT list MUST be in the GROUP BY clause. Always use proper aggregate functions (SUM, AVG, COUNT, etc.) for numeric value columns. TRANSLATION RULE: You MUST translate any hardcoded string aliases, ENUM values, or date formatting (like month names) in the SQL to '{$idiomaStr}'. Return STRICTLY the SQL query, without backticks, without markdown formatting (no ```sql), and without any explanations. CRITICAL: You are strictly forbidden from guessing table or column names. You may ONLY use the tables and columns explicitly provided in the schema context.",
                [],
                []
            );
            
            $prompt = "Available tables and columns (YOU MUST STRICTLY USE ONLY THESE):\n$schemaContext\n\nUser Instruction: $command\n\nTechnical Instruction: Generate the SQL query to fetch this data. CRITICAL RULES:\n1. USE ONLY the tables and columns listed above. Do NOT invent or guess columns that are not in the list.\n2. Verify the column exists in the EXACT table you are querying (e.g. do not use 'data_venda' if querying 'compras' unless it exists there). Prefix columns with table aliases if joining.\n3. Group the data intelligently. Return the first column as the text label and subsequent column(s) as numeric value(s).\n4. Translate all returned text labels (like month names) to '{$idiomaStr}'.";
            
            $response = $agent->prompt($prompt, [], $provider, $sqlModel);
            $sql = $response->text;
            $sql = preg_replace('/```sql\s*/', '', $sql);
            $sql = preg_replace('/```sql\s*/i', '', $sql);
            $sql = preg_replace('/```\s*/', '', $sql);
            $sql = trim($sql);
            
            if (!preg_match('/^\s*(?:SELECT|WITH)\b/i', $sql)) {
                if (preg_match('/(?:SELECT|WITH)\b.*/is', $sql, $matches)) {
                    $sql = trim($matches[0]);
                } else {
                    throw new \Exception(__('filament-data-copilot::messages.Only SELECT queries are allowed for security.'));
                }
            }
            if (preg_match('/;\s*(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE)\b/i', $sql)) {
                throw new \Exception(__('filament-data-copilot::messages.Only SELECT queries are allowed for security.'));
            }
            
            $this->generatedSql = $sql;
            
            try {
                if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
                    \Illuminate\Support\Facades\DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
                }
            } catch (\Exception $e) {}

            // Generate title
            $titleAgent = new \Laravel\Ai\AnonymousAgent(
                "You are a helpful assistant. Create a very short title (maximum 4 to 5 words) summarizing the objective of the following request. Return ONLY the title in the language '{$idiomaStr}', without quotes and without markdown.",
                [],
                []
            );
            $titleResponse = $titleAgent->prompt("Request: " . $command, [], $provider, $textModel);
            $this->reportTitle = trim(str_replace(['"', "'", '`'], '', $titleResponse->text));
            
            // Convert objects to arrays
            $results = \Illuminate\Support\Facades\DB::select($sql);
            $this->resultData = json_decode(json_encode($results), true);
            
            // Generate interpretation if we have data
            if (!empty($this->resultData)) {
                $dataJson = json_encode(array_slice($this->resultData, 0, 50));
                $descAgent = new \Laravel\Ai\AnonymousAgent(
                    "You are a data analyst. Write a concise paragraph (2-3 sentences max) interpreting the following data results for a report. The user's language is '{$idiomaStr}'. Format any monetary values correctly using the currency '{$this->appCurrency}' and locale '{$this->appLocale}'. Provide only the interpretation, explaining what the data shows.",
                    [],
                    []
                );
                $descResponse = $descAgent->prompt("Data: {$dataJson}", [], $provider, $textModel);
                $this->chartDescription = trim(str_replace(['"', "'", '`'], '', $descResponse->text));
            }
            
            $this->uuid = (string) Str::uuid();
            AiReport::create([
                'uuid' => $this->uuid,
                'user_id' => auth()->id(),
                'title' => $this->reportTitle,
                'description' => $this->chartDescription,
                'result_data' => $this->resultData,
                'output_mode' => $this->currentOutputMode,
                'chart_type' => $this->currentChartType,
                'command' => $command,
            ]);
            
            \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Analysis Completed'))->success()->send();
            
            // Force Chart.js to recalculate its dimensions after the DOM has updated
            $this->js('setTimeout(() => window.dispatchEvent(new Event("resize")), 100);');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro na Análise Inteligente: " . $e->getMessage());
            $this->analysisError = $e->getMessage();
            \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Analysis Error'))->body($e->getMessage())->danger()->send();
        }
    }

    public ?string $chartBase64 = null;

    public function downloadPdfWithChart($base64)
    {
        $this->chartBase64 = $base64;
        return $this->downloadPdf();
    }

    public function downloadPdf()
    {
        if (empty($this->resultData)) {
            \Filament\Notifications\Notification::make()->title(__('filament-data-copilot::messages.Warning'))->body(__('filament-data-copilot::messages.No data to export.'))->warning()->send();
            return;
        }

        $pluginSettings = AiReportSetting::first();
        
        if ($pluginSettings) {
            $replacements = [
                '#date' => now()->format('d/m/Y'),
                '#datetime' => now()->format('d/m/Y H:i:s'),
                '#appname' => config('app.name'),
                '#appurl' => config('app.url'),
            ];
            
            $pluginSettings->header_html = strtr($pluginSettings->header_html ?? '', $replacements);
            $pluginSettings->footer_html = strtr($pluginSettings->footer_html ?? '', $replacements);
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
            'pluginSettings' => $pluginSettings,
            'resultData' => $this->resultData,
            'keys' => count($this->resultData) > 0 ? array_keys($this->resultData[0]) : [],
            'currentOutputMode' => $this->currentOutputMode,
            'chartBase64' => $this->chartBase64,
            'reportTitle' => $this->reportTitle,
            'chartDescription' => $this->chartDescription,
            'appCurrency' => $this->appCurrency,
            'appLocale' => $this->appLocale,
        ]);
        
        $pdf->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        
        if ($this->currentOutputMode === 'Gráfico') {
            $pdf->setPaper('a4', 'landscape');
        } else {
            $pdf->setPaper('a4', 'landscape');
        }
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'relatorio-analise.pdf');
    }
}
