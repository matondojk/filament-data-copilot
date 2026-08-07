<?php

namespace Matondojk\FilamentDataCopilot\Pages;

use Matondojk\FilamentDataCopilot\Models\AiReportSetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Laravel\Ai\Ai;
use Illuminate\Support\Facades\Log;

class AiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string | \UnitEnum | null $navigationGroup = 'Análise Inteligente';
    protected string $view = 'filament-data-copilot::pages.ai-settings';
    protected static ?string $slug = 'ai/settings';
    protected static ?string $title = null;

    public function getTitle(): string
    {
        return __('filament-data-copilot::messages.Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-data-copilot::messages.Settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-data-copilot::messages.Smart Reporting');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $setting = AiReportSetting::first();
        $data = $setting ? $setting->toArray() : [];
        if (empty($data['idioma'])) {
            $data['idioma'] = config('app.locale');
        }
        $this->form->fill($data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('filament-data-copilot::messages.Artificial Intelligence Settings'))
                    ->description(__('filament-data-copilot::messages.Define the base language and credentials of your AI provider.'))
                    ->icon('heroicon-o-sparkles')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('idioma')
                                ->label(__('filament-data-copilot::messages.Main Language'))
                                ->options([
                                    'pt_BR' => 'Português (Brasil)',
                                    'pt'    => 'Português (Portugal)',
                                    'en'    => 'English (US)',
                                    'es'    => 'Español (España)',
                                    'fr'    => 'Français (France)',
                                    'ar'    => 'العربية (Arabic)',
                                    'de'    => 'Deutsch (German)',
                                    'it'    => 'Italiano (Italian)',
                                ])
                                ->default(config('app.locale'))
                                ->native(false)
                                ->required(),
                                
                            Select::make('currency')
                                ->label(__('filament-data-copilot::messages.Currency'))
                                ->options($this->getCurrencies())
                                ->default('BRL')
                                ->searchable()
                                ->native(false)
                                ->required(),
                                
                            Select::make('ai_provider')
                                ->label(__('filament-data-copilot::messages.AI Provider'))
                                ->options(function () {
                                    $providers = array_keys(config('ai.providers', []));
                                    $options = [];
                                    foreach ($providers as $provider) {
                                        $options[$provider] = ucfirst($provider);
                                    }
                                    return $options;
                                })
                                ->searchable()
                                ->native(false)
                                ->required(),

                                 TextInput::make('api_key')
                            ->label(__('filament-data-copilot::messages.API Key'))
                            ->password()
                            ->required(),
                        ]),
                        
                    ]),
                    
                Section::make(__('filament-data-copilot::messages.Business Context'))
                    ->description(__('filament-data-copilot::messages.Teach the AI about your business model so it can generate accurate analysis.'))
                    ->icon('heroicon-o-building-office')
                    ->collapsed()
                    ->schema([
                        Select::make('allowed_models')
                            ->label(__('filament-data-copilot::messages.Allowed Data Models'))
                            ->multiple()
                            ->native(false)
                            ->options(fn () => $this->getAvailableModels())
                            ->columnSpanFull(),
                        
                        Textarea::make('business_description')
                            ->label(__('filament-data-copilot::messages.Business Description'))
                            ->helperText(__('filament-data-copilot::messages.Explain the type of business to the AI to customize the results.'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                    
                Section::make(__('filament-data-copilot::messages.Analysis Suggestions'))
                    ->description(__('filament-data-copilot::messages.Manage the suggested prompts for the user.'))
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->collapsed()
                    ->schema([
                        Repeater::make('suggestions')
                            ->label(__('filament-data-copilot::messages.Suggestions'))
                            ->schema([
                                TextInput::make('title')->label(__('filament-data-copilot::messages.Title'))->required(),
                                Textarea::make('prompt')->label(__('filament-data-copilot::messages.Prompt'))->required()->rows(3),
                            ])
                            ->defaultItems(0)
                            ->reorderable(true)
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
                    
                Section::make(__('filament-data-copilot::messages.PDF Report Appearance'))
                    ->description(__('filament-data-copilot::messages.Customize the header and footer of reports generated by Intelligent Analysis.'))
                    ->icon('heroicon-o-paint-brush')
                    ->collapsed()
                    ->schema([
                        RichEditor::make('header_html')
                            ->label(__('filament-data-copilot::messages.Header'))
                            ->helperText(__('filament-data-copilot::messages.When generating the report, this header will be included at the top of the PDF document. Supported magic variables: #date, #datetime, #appname, #appurl'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('settings-attachments')
                            ->fileAttachmentsVisibility('public')
                            ->resizableImages()
                            ->extraInputAttributes(['style' => 'min-height: 190px;'])
                            ->columnSpanFull(),
                        
                        RichEditor::make('footer_html')
                            ->label(__('filament-data-copilot::messages.Footer'))
                            ->helperText(__('filament-data-copilot::messages.When generating the report, this content will be included in the footer of the PDF document. Supported magic variables: #date, #datetime, #appname, #appurl'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('settings-attachments')
                            ->fileAttachmentsVisibility('public')
                            ->resizableImages()
                            ->extraInputAttributes(['style' => 'min-height: 190px;'])
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();
        $setting = AiReportSetting::first();
        
        $oldBusinessDesc = $setting ? $setting->business_description : null;
        
        if (!empty($data['ai_provider']) && !empty($data['api_key'])) {
            config(["ai.providers.{$data['ai_provider']}.key" => $data['api_key']]);
            // If the provider doesn't exist in config, just default it for safe measure
            config(['ai.default' => $data['ai_provider']]);
        }
        
        $textModel = (isset($data['ai_provider']) && $data['ai_provider'] === 'openai') ? 'gpt-4o-mini' : null;
        


        $oldIdioma = AiReportSetting::first()?->idioma;

        AiReportSetting::updateOrCreate(
            ['id' => 1],
            $data
        );
        
        if (!empty($data['idioma'])) {
            app()->setLocale($data['idioma']);
        }

        Notification::make()
            ->title(__('filament-data-copilot::messages.Settings saved successfully!'))
            ->success()
            ->send();
            
        if ($oldIdioma !== $data['idioma']) {
            $this->js('window.location.reload()');
            return;
        }
            
        return redirect(static::getUrl());
    }

    private function getAvailableModels(): array
    {
        $models = [];
        $path = app_path('Models');

        if (! is_dir($path)) {
            return $models;
        }

        $files = \Illuminate\Support\Facades\File::allFiles($path);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $class = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);
            
            if (class_exists($class)) {
                // Salva a string do caminho completo ou apenas o nome, usaremos o basename para exibição
                $models[$class] = class_basename($class);
            }
        }

        return $models;
    }

    protected function getCurrencies(): array
    {
        return [
            'AOA' => 'AOA - Kwanza (Angola)',
            'ARS' => 'ARS - Peso Argentino (Argentina)',
            'AUD' => 'AUD - Australian Dollar (Australia)',
            'BRL' => 'BRL - Real (Brasil)',
            'CAD' => 'CAD - Canadian Dollar (Canada)',
            'CHF' => 'CHF - Schweizer Franken (Schweiz)',
            'CLP' => 'CLP - Peso Chileno (Chile)',
            'CNY' => 'CNY - 人民币 (China)',
            'COP' => 'COP - Peso Colombiano (Colombia)',
            'EUR' => 'EUR - Euro (European Union)',
            'GBP' => 'GBP - British Pound (United Kingdom)',
            'INR' => 'INR - Indian Rupee (India)',
            'JPY' => 'JPY - 日本円 (Japan)',
            'MXN' => 'MXN - Peso Mexicano (México)',
            'MZN' => 'MZN - Metical (Moçambique)',
            'PEN' => 'PEN - Sol (Perú)',
            'PYG' => 'PYG - Guaraní (Paraguay)',
            'RUB' => 'RUB - Российский рубль (Россия)',
            'USD' => 'USD - US Dollar (United States)',
            'UYU' => 'UYU - Peso Uruguayo (Uruguay)',
            'ZAR' => 'ZAR - South African Rand (South Africa)',
        ];
    }
}
