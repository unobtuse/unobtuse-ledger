@props([
    'id' => 'chart-' . uniqid(),
    'height' => '300px',
    'data' => [],
    'labels' => [],
    'datasets' => [],
    'options' => [],
])

<div class="relative" style="height: {{ $height }}">
    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
<script>
    function initChart{{ str_replace('-', '_', $id) }}() {
        const ctx = document.getElementById('{{ $id }}');
        if (!ctx) return null;
        
        // Destroy existing chart if it exists
        if (ctx.chart) {
            ctx.chart.destroy();
        }

        @php
            $defaultDatasets = [
                [
                    'label' => 'Data',
                    'data' => $data,
                    'borderColor' => null,
                    'backgroundColor' => null,
                    'tension' => 0.4,
                    'fill' => false,
                ]
            ];
            $finalDatasets = !empty($datasets) ? $datasets : $defaultDatasets;
        @endphp

        const data = @json($finalDatasets);

        const chartData = {
            labels: @json($labels),
            datasets: data.map((dataset, index) => {
                const colors = window.getChartColorPalette();
                const borderColor = dataset.borderColor || colors[index % colors.length];
                const backgroundColor = dataset.backgroundColor || window.hexToRgba(borderColor, 0.1);
                
                // Ensure we have valid colors (not black in dark mode)
                const theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                const finalBorderColor = (borderColor === '#000000' && theme === 'dark') ? colors[0] : borderColor;
                
                return {
                    ...dataset,
                    borderColor: finalBorderColor,
                    backgroundColor: backgroundColor,
                    borderWidth: dataset.borderWidth || 2,
                    pointRadius: dataset.pointRadius ?? 3,
                    pointHoverRadius: dataset.pointHoverRadius ?? 5,
                    pointBackgroundColor: finalBorderColor,
                };
            })
        };

        const options = @json($options);

        ctx.chart = window.createLineChart('{{ $id }}', chartData, options);
        return ctx.chart;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChart{{ str_replace('-', '_', $id) }});
    } else {
        initChart{{ str_replace('-', '_', $id) }}();
    }

    // Re-initialize on Livewire update
    document.addEventListener('livewire:load', initChart{{ str_replace('-', '_', $id) }});
    document.addEventListener('livewire:update', initChart{{ str_replace('-', '_', $id) }});
    
    // Re-initialize on theme change to update colors
    const chartObserver{{ str_replace('-', '_', $id) }} = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                setTimeout(() => initChart{{ str_replace('-', '_', $id) }}(), 100);
            }
        });
    });
    chartObserver{{ str_replace('-', '_', $id) }}.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
</script>
@endpush
