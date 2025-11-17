@props([
    'id' => 'chart-' . uniqid(),
    'height' => '300px',
    'data' => [],
    'labels' => [],
    'datasets' => [],
    'options' => [],
    'horizontal' => false,
])

<div class="relative" style="height: {{ $height }}">
    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @php
            $defaultDatasets = [
                [
                    'label' => 'Data',
                    'data' => $data,
                    'backgroundColor' => null,
                ]
            ];
            $finalDatasets = !empty($datasets) ? $datasets : $defaultDatasets;
        @endphp

        const data = @json($finalDatasets);

        const chartData = {
            labels: @json($labels),
            datasets: data.map((dataset, index) => {
                const colors = window.getChartColorPalette();
                return {
                    ...dataset,
                    backgroundColor: dataset.backgroundColor || colors[index % colors.length],
                    borderColor: dataset.borderColor || colors[index % colors.length],
                    borderWidth: dataset.borderWidth || 0,
                    borderRadius: dataset.borderRadius ?? 4,
                };
            })
        };

        const defaultOptions = @json($options);

        @if($horizontal)
        defaultOptions.indexAxis = 'y';
        if (defaultOptions.scales) {
            const temp = defaultOptions.scales.x;
            defaultOptions.scales.x = defaultOptions.scales.y || {};
            defaultOptions.scales.y = temp || {};
        }
        @endif

        window.createBarChart('{{ $id }}', chartData, defaultOptions);
    });
</script>
@endpush
