@props([
    'id' => 'chart-' . uniqid(),
    'height' => '300px',
    'data' => [],
    'labels' => [],
    'options' => [],
])

<div class="relative" style="height: {{ $height }}">
    <canvas id="{{ $id }}"></canvas>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colors = window.getChartColorPalette(@json(count($data)));

        const chartData = {
            labels: @json($labels),
            datasets: [{
                data: @json($data),
                backgroundColor: colors,
                borderColor: window.getChartColors().background,
                borderWidth: 2,
                hoverOffset: 8,
            }]
        };

        const options = @json($options);

        window.createDoughnutChart('{{ $id }}', chartData, options);
    });
</script>
@endpush
