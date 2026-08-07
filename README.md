# Filament Data Copilot

An AI-powered data assistant for Filament PHP that turns natural language into charts and SQL reports directly within your Filament admin panel.

## Features
- **Natural Language to SQL**: Talk to your database. Generate precise SQL queries from text.
- **Dynamic Charts**: Instantly render Bar, Line, Pie, Doughnut, Stacked, and Polar Area charts.
- **AI Insights**: Automatically generate brief textual interpretations of the queried data.
- **Export to PDF**: Generate beautiful PDF reports out of your charts and tables.
- **Safe & Restricted**: Restrict the AI to only specific tables via settings. 

## Installation

You can install the package via composer:

```bash
composer require matondojk/filament-data-copilot
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-data-copilot-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-data-copilot-config"
```

## Usage

In your Filament panel provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`), register the plugin:

```php
use Matondojk\FilamentDataCopilot\FilamentDataCopilotPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentDataCopilotPlugin::make());
}
```

Once registered, the "Data Copilot" (Análise Inteligente) and its settings will automatically appear in your navigation menu!

## Setup your AI Provider
Navigate to the "Settings" page inside the Filament panel. 
1. Select your preferred Language.
2. Select your AI Provider (e.g., OpenAI) and paste your API Key.
3. Select which **Models (Tables)** the AI is allowed to query.

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security
If you discover any security related issues, please email matondojk@github.com instead of using the issue tracker.

## License
The MIT License (MIT).
