# Filament Data Copilot

**Filament Data Copilot** is a powerful AI-driven assistant for Filament PHP that allows you to chat with your database. Turn plain natural language into precise SQL queries, interactive charts, textual insights, and automated PDF reports directly inside your Filament Admin Panel.

---

## Features

- **Natural Language to SQL**: Stop writing complex SQL queries manually. Just ask for what you want in plain text.
- **Dynamic & Interactive Charts**: Automatically renders data as Bar, Line, Pie, Doughnut, Stacked, and Polar Area charts.
- **AI-Generated Insights**: Provides a brief, intelligent textual interpretation of the data fetched.
- **Safe & Restricted Context**: Strictly limit which database tables and columns the AI is allowed to query to prevent unauthorized data access.
- **Scheduled Reports via Email**: You can easily schedule report generation and receive updates directly in your inbox daily, weekly, or monthly.
- **PDF Exports**: Export your charts and tables to beautiful PDF files.
- **Multi-language Support**: Automatically adapts to your application's locale, translating dynamic SQL outputs (like months and statuses) seamlessly.

---

## Supported AI Providers

Powered by the robust Laravel AI SDK, this package supports multiple Large Language Models out-of-the-box:
- **OpenAI** (GPT-4o, GPT-4o-mini)
- **DeepSeek** 
- **Azure OpenAI**
- **Anthropic**
- And all other providers supported by `laravel-ai/laravel-ai`!

---

## Installation

You can install the package via composer:

```bash
composer require matondojk/filament-data-copilot
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="filament-data-copilot-migrations"
php artisan migrate
```

*(Optional)* Publish the config file:

```bash
php artisan vendor:publish --tag="filament-data-copilot-config"
```

---

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

Once registered, the **Data Copilot** (Análise Inteligente) and its **Settings** pages will appear in your Filament navigation menu.

---

## Configuration

Before making your first query, you must set up the AI context:

1. Navigate to the **Settings** page in your Filament panel.
2. Select your preferred base language and currency.
3. Select your **AI Provider** (e.g., OpenAI) and paste your **API Key**.
4. **Allowed Models**: Select which Eloquent Models (Tables) the AI is authorized to read. *The AI will strictly be blocked from accessing any table not selected here.*
5. **Business Context**: Provide a short description of your business to give the AI context for better insights.

---

## Prompt Examples

Not sure what to ask? Here are some examples of what you can type into the Data Copilot:

### Sales & Revenue
> "Mostre o total de vendas realizadas por mês neste ano, agrupando pelo status do pedido."
> 
> "Show me the total revenue generated grouped by product category for the last 6 months."

### Operations & Logistics
> "Apresente a evolução temporal das compras e vendas em base diária. Mostre total comprado, total vendido e a variação. Identifique tendências entre reposição de estoque e ritmo de vendas."

### Customers
> "Quais foram os 5 clientes que mais compraram na nossa loja em valor total no último trimestre?"

---

## Security

If you discover any security-related issues, please email **matondojk@github.com** instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
