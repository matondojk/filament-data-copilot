<?php

namespace Matondojk\FilamentDataCopilot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'result_data',
        'output_mode',
        'chart_type',
        'command',
        'is_scheduled',
        'schedule_frequency',
        'schedule_time',
        'schedule_email',
    ];

    protected $casts = [
        'result_data' => 'array',
        'is_scheduled' => 'boolean',
    ];

    public function getChartBase64(): ?string
    {
        if ($this->output_mode !== 'Gráfico' || empty($this->result_data)) {
            return null;
        }

        $keys = array_keys($this->result_data[0]);
        if (count($keys) < 2) return null;

        $labelKey = $keys[0];
        $labels = array_column($this->result_data, $labelKey);
        
        $datasetsData = [];
        $possibleValueKeys = array_slice($keys, 1);
        
        $bgColors = [
            'rgba(59, 130, 246, 0.7)', 'rgba(16, 185, 129, 0.7)', 'rgba(245, 158, 11, 0.7)',
            'rgba(239, 68, 68, 0.7)', 'rgba(139, 92, 246, 0.7)', 'rgba(236, 72, 153, 0.7)'
        ];
        $borderColors = [
            'rgba(59, 130, 246, 1)', 'rgba(16, 185, 129, 1)', 'rgba(245, 158, 11, 1)',
            'rgba(239, 68, 68, 1)', 'rgba(139, 92, 246, 1)', 'rgba(236, 72, 153, 1)'
        ];

        $chartType = $this->chart_type ?? 'bar';
        $isPie = in_array($chartType, ['pie', 'doughnut', 'polarArea']);

        if ($isPie) {
            // For pie charts, colors are per data point
            $colors = [];
            $borders = [];
            for ($i = 0; $i < count($labels); $i++) {
                $colors[] = $bgColors[$i % count($bgColors)];
                $borders[] = $borderColors[$i % count($borderColors)];
            }
            $datasetsData[] = [
                'label' => mb_strtoupper(str_replace('_', ' ', $possibleValueKeys[0])),
                'data' => array_map('floatval', array_column($this->result_data, $possibleValueKeys[0])),
                'backgroundColor' => $colors,
                'borderColor' => $borders,
                'borderWidth' => 1
            ];
        } else {
            foreach ($possibleValueKeys as $index => $vKey) {
                $type = $chartType;
                if ($chartType === 'combo') $type = $index === 0 ? 'bar' : 'line';
                elseif ($chartType === 'stacked') $type = 'bar';
                elseif ($chartType === 'multi-axis') $type = 'line';

                $cIndex = $index % count($bgColors);
                $bg = $bgColors[$cIndex];
                $border = $borderColors[$cIndex];

                if (count($possibleValueKeys) === 1 && $type === 'bar') {
                    $bg = array_map(fn($i) => $bgColors[$i % count($bgColors)], array_keys($labels));
                    $border = array_map(fn($i) => $borderColors[$i % count($borderColors)], array_keys($labels));
                }

                $datasetsData[] = [
                    'type' => $type,
                    'label' => mb_strtoupper(str_replace('_', ' ', $vKey)),
                    'data' => array_map('floatval', array_column($this->result_data, $vKey)),
                    'backgroundColor' => $bg,
                    'borderColor' => $border,
                    'borderWidth' => 2,
                    'fill' => ($type === 'line') ? false : true,
                ];
            }
        }

        $config = [
            'type' => $isPie ? $chartType : ($chartType === 'stacked' || $chartType === 'combo' ? 'bar' : ($chartType === 'multi-axis' ? 'line' : $chartType)),
            'data' => [
                'labels' => $labels,
                'datasets' => $datasetsData
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['display' => true, 'position' => 'bottom']
                ]
            ]
        ];

        if ($chartType === 'stacked') {
            $config['options']['scales'] = [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true]
            ];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post('https://quickchart.io/chart', [
                'chart' => json_encode($config),
                'width' => 800,
                'height' => 400,
                'format' => 'base64',
                'backgroundColor' => 'white'
            ]);
            
            if ($response->successful()) {
                // The API returns the raw base64 string or an object depending on the request.
                // When we pass 'format' => 'base64', it returns a raw string or we can just request an image directly
                $imageContent = $response->body();
                // If it starts with data:image, it's ready. If not, it might be the raw image blob or just the base64 string
                if (str_starts_with($imageContent, 'iVBORw0KGgo')) {
                    return 'data:image/png;base64,' . $imageContent;
                }
                // If we didn't get base64 directly from post, we can just get the raw image
                $response = \Illuminate\Support\Facades\Http::get('https://quickchart.io/chart', [
                    'c' => json_encode($config),
                    'w' => 800,
                    'h' => 400,
                    'bkg' => 'white'
                ]);
                if ($response->successful()) {
                    return 'data:image/png;base64,' . base64_encode($response->body());
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error generating QuickChart: " . $e->getMessage());
        }

        return null;
    }
}
