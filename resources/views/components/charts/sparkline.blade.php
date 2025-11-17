@props([
    'id' => 'chart-' . uniqid(),
    'height' => '40px',
    'data' => [],
    'labels' => [],
    'color' => null,
])

<div class="relative" style="height: {{ $height }}">
    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
<script>
    function initSparkline{{ str_replace('-', '_', $id) }}() {
        const ctx = document.getElementById('{{ $id }}');
        if (!ctx) return null;
        
        // Destroy existing chart if it exists
        if (ctx.chart) {
            ctx.chart.destroy();
        }

        const sparklineData = {
            data: @json($data),
            labels: @json($labels),
        };

        const color = @json($color);
        
        // Use chart-1 color if no color specified
        const theme = window.getChartColors();
        let lineColor = color || theme.chart1;
        
        // Ensure we have a valid color (not black in dark mode)
        const isDarkMode = document.documentElement.classList.contains('dark');
        if (lineColor === '#000000' && isDarkMode) {
            lineColor = theme.chart1 || '#ffffff';
        }

        ctx.chart = window.createSparkline('{{ $id }}', sparklineData, lineColor);
        return ctx.chart;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSparkline{{ str_replace('-', '_', $id) }});
    } else {
        initSparkline{{ str_replace('-', '_', $id) }}();
    }

    // Re-initialize on Livewire update
    document.addEventListener('livewire:load', initSparkline{{ str_replace('-', '_', $id) }});
    document.addEventListener('livewire:update', initSparkline{{ str_replace('-', '_', $id) }});
    
    // Re-initialize on theme change
    const sparklineObserver{{ str_replace('-', '_', $id) }} = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                setTimeout(() => initSparkline{{ str_replace('-', '_', $id) }}(), 100);
            }
        });
    });
    sparklineObserver{{ str_replace('-', '_', $id) }}.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
</script>
@endpush
