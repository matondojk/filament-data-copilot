<x-filament-panels::page>
    @if(is_null($resultData))
        <form wire:submit="submit">
            {{ $this->form }}

            <div style="margin-top: 5px; padding-top: 5px;">
                <x-filament::button type="submit" color="primary">
                    <span wire:loading.remove wire:target="submit">{{ __('filament-data-copilot::messages.Generate Report') }}</span>
                    <span wire:loading wire:target="submit">{{ __('filament-data-copilot::messages.Processing...') }}</span>
                </x-filament::button>
            </div>
        </form>

        @if($analysisError)
            <div class="mt-8 p-4 bg-danger-50 text-danger-600 rounded-xl border border-danger-200">
                <strong>Erro:</strong> {{ $analysisError }}
            </div>
        @endif
    @else
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 0.5rem; margin-bottom: 1rem;">
            <button type="button" wire:click="$set('resultData', null)" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 border border-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:border-white/10 dark:hover:bg-white/10">
                &larr; {{ __('filament-data-copilot::messages.Back to Filters') }}
            </button>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                {{ $this->scheduleReportAction }}
                
                <button type="button" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                    x-on:click.prevent="
                        const canvas = document.querySelector('#pdf-report-container canvas');
                        if (canvas) {
                            $wire.downloadPdfWithChart(canvas.toDataURL('image/png'));
                        } else {
                            $wire.downloadPdf();
                        }
                    ">
                    {{ __('filament-data-copilot::messages.Download PDF') }}
                </button>
            </div>
        </div>

        <div id="pdf-report-container" class="w-full mt-6" x-data="{ init() { $nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }); } }">
            <div>
                @php
                    $keys = (is_array($resultData) && count($resultData) > 0) ? array_keys($resultData[0]) : [];
                    $labelKey = $keys[0] ?? null;
                    $valueKey = $keys[1] ?? $keys[0] ?? null;

                    $labels = array_column($resultData, $labelKey);
                    
                    $datasetsData = [];
                    if (count($keys) > 1) {
                        $possibleValueKeys = array_slice($keys, 1);
                        foreach ($possibleValueKeys as $vKey) {
                            $datasetsData[] = [
                                'label' => mb_strtoupper(str_replace('_', ' ', $vKey)),
                                'data' => array_map('floatval', array_column($resultData, $vKey))
                            ];
                        }
                    } else {
                        $datasetsData[] = [
                            'label' => 'Valor',
                            'data' => []
                        ];
                    }
                    
                    // Helper to format values
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

                @if($currentOutputMode === 'Gráfico' && is_array($resultData) && count($resultData) > 0 && is_array($keys) && count($keys) >= 2)
                    <div class="mb-10 flex flex-wrap gap-6 justify-center">
                        @foreach(['bar' => __('filament-data-copilot::messages.Bar'), 'line' => __('filament-data-copilot::messages.Line'), 'pie' => __('filament-data-copilot::messages.Pie'), 'doughnut' => __('filament-data-copilot::messages.Doughnut'), 'stacked' => __('filament-data-copilot::messages.Stacked Bar'), 'multi-axis' => __('filament-data-copilot::messages.Multi-Axis Line'), 'polarArea' => __('filament-data-copilot::messages.Polar Area'), 'combo' => __('filament-data-copilot::messages.Combo (Bar + Line)')] as $key => $label)
                            <label class="flex items-center gap-1.5 cursor-pointer group">
                                <input type="radio" wire:model.live="currentChartType" value="{{ $key }}" class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500 rounded-full transition-all bg-white dark:bg-white/10 dark:border-white/20">
                                <span class="font-medium text-gray-700 dark:text-gray-200 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" style="font-size: 14px;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="p-4 flex justify-center"
                        x-data="{
                            chartType: @entangle('currentChartType'),
                            labels: {{ \Illuminate\Support\Js::from($labels) }},
                            datasetsData: {{ \Illuminate\Support\Js::from($datasetsData) }},
                            currencyCode: '{{ $appCurrency ?? "BRL" }}',
                            localeCode: '{{ str_replace("_", "-", $appLocale ?? "pt-BR") }}',
                            chart: null,
                            formatVal(val) {
                                if (isNaN(val)) return val;
                                return new Intl.NumberFormat(this.localeCode, { style: 'currency', currency: this.currencyCode }).format(val);
                            },
                            init() {
                                if (typeof Chart === 'undefined') {
                                    const script = document.createElement('script');
                                    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                    script.onload = () => setTimeout(() => this.renderChart(), 50);
                                    document.head.appendChild(script);
                                } else {
                                    setTimeout(() => this.renderChart(), 50);
                                }
                                
                                $watch('labels', () => this.renderChart());
                                $watch('chartType', () => this.renderChart());
                            },
                            renderChart() {
                                const ctx = this.$refs.canvas;
                                
                                const existingChart = Chart.getChart(ctx);
                                if (existingChart) {
                                    existingChart.destroy();
                                }
                                if (this.chart) {
                                    this.chart.destroy();
                                }
                                
                                const getColors = (count) => {
                                    const colors = [
                                        'rgba(59, 130, 246, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(245, 158, 11, 0.7)',
                                        'rgba(239, 68, 68, 0.7)', 'rgba(139, 92, 246, 0.7)', 'rgba(236, 72, 153, 0.7)',
                                        'rgba(14, 165, 233, 0.7)', 'rgba(249, 115, 22, 0.7)', 'rgba(168, 85, 247, 0.7)'
                                    ];
                                    let result = [];
                                    for (let i = 0; i < count; i++) {
                                        result.push(colors[i % colors.length]);
                                    }
                                    return result;
                                };

                                const isPie = (this.chartType === 'pie' || this.chartType === 'doughnut' || this.chartType === 'polarArea');
                                const bgColors = getColors(Math.max(this.labels.length, this.datasetsData.length * 2));
                                const borderColors = bgColors.map(c => c.replace('0.7)', '1)'));

                                let formattedDatasets = [];
                                
                                if (isPie) {
                                    formattedDatasets = [{
                                        label: this.datasetsData[0].label,
                                        data: this.datasetsData[0].data,
                                        backgroundColor: bgColors,
                                        borderColor: borderColors,
                                        borderWidth: 1
                                    }];
                                } else {
                                    this.datasetsData.forEach((ds, index) => {
                                        let type = this.chartType;
                                        let yAxisID = 'y';
                                        
                                        if (this.chartType === 'combo') {
                                            type = index === 0 ? 'bar' : 'line';
                                        } else if (this.chartType === 'stacked') {
                                            type = 'bar';
                                        } else if (this.chartType === 'multi-axis') {
                                            type = 'line';
                                            yAxisID = index === 0 ? 'y' : 'y1';
                                        }
                                        
                                        let bg = bgColors[index % bgColors.length];
                                        let border = borderColors[index % borderColors.length];
                                        
                                        if (this.datasetsData.length === 1 && type === 'bar') {
                                            bg = bgColors;
                                            border = borderColors;
                                        }
                                        
                                        formattedDatasets.push({
                                            type: type,
                                            label: ds.label,
                                            data: ds.data,
                                            backgroundColor: bg,
                                            borderColor: border,
                                            borderWidth: 2,
                                            borderRadius: (type === 'bar') ? 4 : 0,
                                            fill: (type === 'line') ? false : true,
                                            yAxisID: yAxisID
                                        });
                                    });
                                }
                                
                                let chartOptions = {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    animation: false,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'top',
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: (context) => {
                                                    let val = context.raw;
                                                    if (context.parsed && context.parsed.y !== undefined) val = context.parsed.y;
                                                    let label = context.dataset.label || '';
                                                    if (label) { label += ': '; }
                                                    return label + this.formatVal(val);
                                                }
                                            }
                                        }
                                    }
                                };
                                
                                if (this.chartType === 'stacked') {
                                    chartOptions.scales = {
                                        x: { stacked: true },
                                        y: { stacked: true, ticks: { callback: (value) => this.formatVal(value) } }
                                    };
                                } else if (this.chartType === 'multi-axis') {
                                    chartOptions.scales = {
                                        y: {
                                            type: 'linear',
                                            display: true,
                                            position: 'left',
                                            ticks: { callback: (value) => this.formatVal(value) }
                                        },
                                        y1: {
                                            type: 'linear',
                                            display: true,
                                            position: 'right',
                                            grid: { drawOnChartArea: false },
                                            ticks: { callback: (value) => this.formatVal(value) }
                                        }
                                    };
                                } else if (!isPie) {
                                    chartOptions.scales = {
                                        y: {
                                            ticks: {
                                                callback: (value) => this.formatVal(value)
                                            }
                                        }
                                    };
                                }

                                this.chart = new Chart(ctx, {
                                    type: isPie ? this.chartType : 'bar',
                                    data: {
                                        labels: this.labels,
                                        datasets: formattedDatasets
                                    },
                                    options: chartOptions
                                });
                            }
                        }"
                    >
                        <div class="w-full flex justify-center" wire:ignore>
                            <div class="w-full" x-bind:style="(chartType === 'pie' || chartType === 'doughnut' || chartType === 'polarArea') ? 'height: 380px; max-width: 440px; margin: 0 auto;' : 'height: 450px;'">
                                <canvas x-ref="canvas"></canvas>
                            </div>
                        </div>
                    </div>
                @else
                    <style>
                        .table-scrollbar::-webkit-scrollbar {
                            width: 6px;
                            height: 6px;
                        }
                        .table-scrollbar::-webkit-scrollbar-track {
                            background: transparent;
                        }
                        .table-scrollbar::-webkit-scrollbar-thumb {
                            background-color: #cbd5e1;
                            border-radius: 10px;
                        }
                        .dark .table-scrollbar::-webkit-scrollbar-thumb {
                            background-color: #334155;
                        }
                        .table-scrollbar {
                            scrollbar-width: thin;
                            scrollbar-color: #cbd5e1 transparent;
                        }
                        .dark .table-scrollbar {
                            scrollbar-color: #334155 transparent;
                        }
                    </style>
                    <div class="overflow-hidden">
                        @if(is_array($resultData) && count($resultData) > 0)
                            <div class="overflow-x-auto table-scrollbar max-h-[600px] overflow-y-auto">
                                <table class="w-full text-left table-auto border-collapse">
                                    <thead class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                        <tr>
                                            @foreach($keys as $key)
                                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                                    {{ str_replace('_', ' ', $key) }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                        @foreach($resultData as $row)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                                @foreach($keys as $key)
                                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                        {{ formatReportValue($key, $row[$key], $appCurrency, $appLocale) }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            @if(!empty($this->generatedSql))
                            <details class="mt-6 w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                                <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 outline-none flex items-center justify-between">
                                    {{ __('filament-data-copilot::messages.View Generated SQL') }}
                                    <x-filament::icon icon="heroicon-o-chevron-down" class="w-4 h-4 text-gray-500" />
                                </summary>
                                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 overflow-x-auto text-left">
                                    <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono">{{ $this->generatedSql }}</pre>
                                </div>
                            </details>
                            @endif
                        @else
                            <div class="px-6 py-12 flex flex-col items-center justify-center text-center">
                                <div class="mt-4 flex flex-col items-center justify-center space-y-4">
                                    <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg max-w-2xl w-full text-center border border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-gray-200 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400">
                                            <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="w-6 h-6" />
                                        </div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('filament-data-copilot::messages.No data returned') }}</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('filament-data-copilot::messages.The intelligent analysis returned no records for this request.') }}
                                        </p>
                                    </div>
                                    
                                    @if(!empty($this->generatedSql))
                                    <details class="w-full max-w-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 outline-none flex items-center justify-between">
                                            {{ __('filament-data-copilot::messages.View Generated SQL') }}
                                            <x-filament::icon icon="heroicon-o-chevron-down" class="w-4 h-4 text-gray-500" />
                                        </summary>
                                        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 overflow-x-auto text-left">
                                            <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono">{{ $this->generatedSql }}</pre>
                                        </div>
                                    </details>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($chartDescription) || !empty($reportTitle))
                    <div style="margin-top: 35px;">
                        <x-filament::section>
                            <x-slot name="heading">
                                <span style="font-size: 18px; font-weight: 600;">{{ $reportTitle ?? 'Análise' }}</span>
                            </x-slot>
                            
                            @if(!empty($chartDescription))
                                <div class="prose max-w-none text-gray-700 dark:text-gray-300 dark:prose-invert" style="font-size: 15px; line-height: 1.6;">
                                    {!! \Illuminate\Support\Str::markdown($chartDescription) !!}
                                </div>
                            @endif
                        </x-filament::section>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-filament-panels::page>
