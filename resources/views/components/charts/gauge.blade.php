@props([
    'id' => 'chart-' . uniqid(),
    'height' => '200px',
    'value' => 0,
    'max' => 100,
    'label' => '',
    'colors' => ['#ef4444', '#f59e0b', '#10b981'], // red, amber, green
    'thresholds' => [33, 66], // percentage thresholds for color zones
])

<div class="relative" style="height: {{ $height }}">
    <canvas id="{{ $id }}"></canvas>
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="top: 20%;">
            <div class="text-center">
                <div class="text-3xl font-bold text-card-foreground">{{ $value }}</div>
                @if($label)
                <div class="text-sm text-muted-foreground mt-1">{{ $label }}</div>
                @endif
            </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('{{ $id }}');
        if (!ctx) return;

        const value = @json($value);
        const max = @json($max);
        const colors = @json($colors);
        const thresholds = @json($thresholds);

        // Calculate percentage
        const percentage = Math.min((value / max) * 100, 100);

        // Determine color based on thresholds
        let gaugeColor;
        if (percentage < thresholds[0]) {
            gaugeColor = colors[0]; // red zone
        } else if (percentage < thresholds[1]) {
            gaugeColor = colors[1]; // amber zone
        } else {
            gaugeColor = colors[2]; // green zone
        }

        const data = {
            datasets: [{
                data: [percentage, 100 - percentage],
                backgroundColor: [
                    gaugeColor,
                    window.getChartColors().border
                ],
                borderWidth: 0,
                circumference: 180,
                rotation: 270,
            }]
        };

        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            },
            cutout: '75%',
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: options
        });
    });
</script>
@endpush
