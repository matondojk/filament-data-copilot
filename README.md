# Filament Data Copilot

**Filament Data Copilot** is a powerful AI-driven assistant for Filament PHP that allows you to chat with your database. Turn plain natural language into precise SQL queries, interactive charts, textual insights, and automated PDF reports directly inside your Filament Admin Panel.


## Table of Contents

- [Features](#features)
- [Supported AI Providers](#supported-ai-providers)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Prompt Examples](#prompt-examples)
- [Security](#security)
- [License](#license)


## Features

- **Natural Language to SQL**: Stop writing complex SQL queries manually. Just ask for what you want in plain text.
- **Dynamic & Interactive Charts**: Automatically renders data as Bar, Line, Pie, Doughnut, Stacked, and Polar Area charts.
- **AI-Generated Insights**: Provides a brief, intelligent textual interpretation of the data fetched.
- **Safe & Restricted Context**: Strictly limit which database tables and columns the AI is allowed to query to prevent unauthorized data access.
- **Scheduled Reports via Email**: You can easily schedule report generation and receive updates directly in your inbox daily, weekly, or monthly.
- **PDF Exports**: Export your charts and tables to beautiful PDF files.
- **Multi-language Support**: Automatically adapts to your application's locale. Prompt the AI in any language (English, Spanish, French, Arabic, etc.), and the AI will dynamically translate SQL outputs seamlessly.


## Supported AI Providers

Powered by the robust [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk), this package supports multiple Large Language Models out-of-the-box:
- **OpenAI** (GPT-4o, GPT-4o-mini)
- **Google Gemini**
- **DeepSeek** 
- **Azure OpenAI**
- **Anthropic**
- And all other providers supported by the `laravel/ai` package!


## Installation

You can install the package via composer:

```shell
composer require matondojk/filament-data-copilot
```

Publish and run the migrations:

```shell
php artisan vendor:publish --tag="filament-data-copilot-migrations"
php artisan migrate
```

Since this package uses custom frontend assets and Tailwind styling, you must build your assets:

```shell
npm install
npm run build
```

*(Optional)* Publish the config file:

```shell
php artisan vendor:publish --tag="filament-data-copilot-config"
```

*(Optional)* Publish the translations:

```shell
php artisan vendor:publish --tag="filament-data-copilot-translations"
```


## Usage

Register the plugin in your Filament Panel Provider (usually `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Matondojk\FilamentDataCopilot\FilamentDataCopilotPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentDataCopilotPlugin::make());
}
```

Once registered, the **Data Copilot** and its **Settings** pages will appear in your Filament navigation menu.


## Scheduling Reports

This package allows users to schedule reports directly from the interface to be sent via email on a daily, weekly, or monthly basis.

To ensure these scheduled emails are actually processed and sent out automatically, you must register the package's console command in your application's scheduler (typically located in `routes/console.php`):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ai:send-scheduled-reports')->hourly();
```

*Note: Make sure your server's Cron is properly configured to run `php artisan schedule:run` every minute as per the official Laravel documentation.*


## Configuration

Before making your first query, you must set up the AI context:

1. Navigate to the **Settings** page in your Filament panel.
2. Select your preferred base language and currency.
3. Select your **AI Provider** (e.g., OpenAI) and paste your **API Key**.
4. **Allowed Models**: Select which Eloquent Models (Tables) the AI is authorized to read. *The AI will strictly be blocked from accessing any table not selected here.*
5. **Business Context**: Provide a short description of your business to give the AI context for better insights.


## Prompt Examples

Because the package is completely multi-language, you can prompt the AI in any language you want. Here are some examples of what you can type into the Data Copilot:

### Sales & Revenue
> "Show me the total revenue generated grouped by product category for the last 6 months."
> 
> "Show the total sales made per month this year, grouped by order status."

### Operations & Logistics
> "Present the temporal evolution of purchases and sales on a daily basis. Show total bought, total sold, and the variation. Identify trends between inventory replacement and sales rhythm."

### Customers
> "Who were the top 5 customers that purchased the most in our store by total value in the last quarter?"


## Security

If you discover any security-related issues, please use the issue tracker directly.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
