import {
    Chart,
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    BarController,
    LineController,
    DoughnutController,
    CategoryScale,
    LinearScale,
    TimeScale,
    Tooltip,
    Legend,
    Title,
    Filler
} from 'chart.js';

// Register Chart.js components
Chart.register(
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    BarController,
    LineController,
    DoughnutController,
    CategoryScale,
    LinearScale,
    TimeScale,
    Tooltip,
    Legend,
    Title,
    Filler
);

// Read OKLCH color from CSS custom property and convert to RGB hex
function oklchToRgbHex(oklchValue) {
    if (!oklchValue || !oklchValue.includes('oklch')) {
        return oklchValue; // Return as-is if not OKLCH
    }
    
    try {
        // Create a temporary element to compute the color
        const tempEl = document.createElement('div');
        tempEl.style.color = oklchValue;
        tempEl.style.position = 'absolute';
        tempEl.style.visibility = 'hidden';
        document.body.appendChild(tempEl);
        const computedColor = window.getComputedStyle(tempEl).color;
        document.body.removeChild(tempEl);
        
        // Convert rgb(r, g, b) to hex
        const rgbMatch = computedColor.match(/\d+/g);
        if (rgbMatch && rgbMatch.length >= 3) {
            const r = parseInt(rgbMatch[0]).toString(16).padStart(2, '0');
            const g = parseInt(rgbMatch[1]).toString(16).padStart(2, '0');
            const b = parseInt(rgbMatch[2]).toString(16).padStart(2, '0');
            return `#${r}${g}${b}`;
        }
    } catch (e) {
        console.warn('Error converting OKLCH to RGB:', e);
    }
    
    // Fallback based on theme
    const theme = getTheme();
    return theme === 'dark' ? '#ffffff' : '#000000';
}

// Read color from CSS custom property
function getCssColor(propertyName) {
    try {
        const value = getComputedStyle(document.documentElement).getPropertyValue(propertyName).trim();
        if (value && value.includes('oklch')) {
            const hexColor = oklchToRgbHex(value);
            // If conversion failed and returned black, try again or use fallback
            if (hexColor === '#000000' && getTheme() === 'dark') {
                // In dark mode, use a light color as fallback
                return '#ffffff'; // White fallback for dark mode
            }
            return hexColor;
        }
        // If value exists but isn't OKLCH, return it
        if (value) {
            return value;
        }
    } catch (e) {
        console.warn('Error reading CSS color:', propertyName, e);
    }
    
    // Fallback based on theme
    const theme = getTheme();
    return theme === 'dark' ? '#ffffff' : '#000000';
}

// Get current theme (light or dark)
function getTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

// Get theme colors from CSS custom properties (design system OKLCH colors)
function getColors() {
    const theme = getTheme();
    
    return {
        primary: getCssColor('--color-primary'),
        success: getCssColor('--color-chart-2'),
        danger: getCssColor('--color-destructive'),
        warning: getCssColor('--color-chart-3'),
        info: getCssColor('--color-chart-2'),
        purple: getCssColor('--color-chart-4'),
        pink: getCssColor('--color-chart-5'),
        chart1: getCssColor('--color-chart-1'),
        chart2: getCssColor('--color-chart-2'),
        chart3: getCssColor('--color-chart-3'),
        chart4: getCssColor('--color-chart-4'),
        chart5: getCssColor('--color-chart-5'),
        chart6: getCssColor('--color-chart-3'), // Fallback to chart-3
        text: getCssColor('--color-foreground'),
        textMuted: getCssColor('--color-muted-foreground'),
        border: getCssColor('--color-border'),
        background: getCssColor('--color-background'),
        gridLines: theme === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
    };
}

// Chart color palette for multi-series charts (reads from CSS custom properties)
function getChartColorPalette(count = 6) {
    const colors = getColors();
    const palette = [
        colors.chart1,
        colors.chart2,
        colors.chart3,
        colors.chart4,
        colors.chart5,
        colors.chart6,
    ];
    return count ? palette.slice(0, count) : palette;
}

// Convert hex to rgba
function hexToRgba(hex, alpha = 1) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// Set Chart.js default configuration
function setChartDefaults() {
    const theme = getColors();

    Chart.defaults.font.family = "'Geist', 'system-ui', '-apple-system', 'sans-serif'";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = theme.textMuted;
    Chart.defaults.borderColor = theme.border;
    Chart.defaults.backgroundColor = theme.background;

    // Default plugin options
    Chart.defaults.plugins.legend.labels.color = theme.text;
    Chart.defaults.plugins.legend.labels.padding = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';

    Chart.defaults.plugins.tooltip.backgroundColor = theme.background;
    Chart.defaults.plugins.tooltip.titleColor = theme.text;
    Chart.defaults.plugins.tooltip.bodyColor = theme.text;
    Chart.defaults.plugins.tooltip.borderColor = theme.border;
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.displayColors = true;

    Chart.defaults.scale.grid.color = theme.gridLines;
    Chart.defaults.scale.ticks.color = theme.textMuted;
}

// Initialize defaults
setChartDefaults();

// Watch for theme changes and update all charts
const chartThemeObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            setChartDefaults();
            // Update all existing charts
            if (typeof Chart !== 'undefined' && Chart.helpers && Chart.helpers.each) {
                Chart.helpers.each(Chart.instances, (chart) => {
                    const colors = getColors();
                    if (chart.options && chart.options.scales) {
                        if (chart.options.scales.x) {
                            if (chart.options.scales.x.ticks) chart.options.scales.x.ticks.color = colors.textMuted;
                            if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = colors.gridLines;
                        }
                        if (chart.options.scales.y) {
                            if (chart.options.scales.y.ticks) chart.options.scales.y.ticks.color = colors.textMuted;
                            if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = colors.gridLines;
                        }
                    }
                    chart.update('none'); // Update without animation
                });
            }
        }
    });
});

chartThemeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
});

// Helper function to create a line chart
window.createLineChart = function(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const theme = getColors();

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
            },
            tooltip: {
                enabled: true,
            }
        },
        scales: {
            x: {
                grid: {
                    display: false,
                    color: theme.gridLines,
                },
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 12,
                    color: theme.textMuted,
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: theme.gridLines,
                },
                ticks: {
                    color: theme.textMuted,
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    };

    const mergedOptions = { ...defaultOptions, ...options };

    return new Chart(ctx, {
        type: 'line',
        data: data,
        options: mergedOptions
    });
};

// Helper function to create a bar chart
window.createBarChart = function(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const theme = getColors();

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: {
            legend: {
                display: data.datasets.length > 1,
                position: 'bottom',
            }
        },
        scales: {
            x: {
                grid: {
                    display: false,
                    color: theme.gridLines,
                },
                ticks: {
                    color: theme.textMuted,
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: theme.gridLines,
                },
                ticks: {
                    color: theme.textMuted,
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    };

    const mergedOptions = { ...defaultOptions, ...options };

    return new Chart(ctx, {
        type: 'bar',
        data: data,
        options: mergedOptions
    });
};

// Helper function to create a doughnut chart
window.createDoughnutChart = function(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const theme = getColors();

    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: $${value.toLocaleString()} (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '70%',
    };

    const mergedOptions = { ...defaultOptions, ...options };

    return new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: mergedOptions
    });
};

// Helper function to create a sparkline
window.createSparkline = function(canvasId, data, color = null) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const theme = getColors();
    const lineColor = color || theme.primary;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || Array(data.data.length).fill(''),
            datasets: [{
                data: data.data,
                borderColor: lineColor,
                backgroundColor: hexToRgba(lineColor, 0.1),
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 0,
            }]
        },
        options: {
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
            scales: {
                x: {
                    display: false
                },
                y: {
                    display: false
                }
            },
            elements: {
                line: {
                    borderWidth: 2
                }
            }
        }
    });
};

// Export utilities
window.Chart = Chart;
window.getChartColors = getColors;
window.getChartColorPalette = getChartColorPalette;
window.hexToRgba = hexToRgba;

export { Chart, getColors, getChartColorPalette, hexToRgba };
