import { useMemo } from 'react';
import ReactApexChart from 'react-apexcharts';
import type { ApexOptions } from 'apexcharts';

/**
 * Local Chart wrapper mirroring the small subset of the
 * `@artisanpack-ui/react/chart` API used by the dashboard. The vendor
 * component's pre-built ESM chunk imports `react-apexcharts` in a way
 * that trips ESM/CJS default-interop under Vite ("Element type is
 * invalid… Check the render method of `Chart`."); the local wrapper
 * imports `react-apexcharts` directly the same way the analytics
 * dashboard does, which Vite handles correctly.
 */
export type DashChartType = 'bar' | 'line' | 'area' | 'donut' | 'pie';

type SemanticColor =
    | 'primary'
    | 'secondary'
    | 'accent'
    | 'success'
    | 'warning'
    | 'error'
    | 'info'
    | 'neutral';

export interface DashChartDataPoint {
    label: string;
    value: number;
    color?: SemanticColor | string;
}

export interface DashChartSeries {
    name: string;
    data: number[];
    color?: SemanticColor | string;
}

export interface DashChartProps {
    type: DashChartType;
    labels?: string[];
    series?: DashChartSeries[];
    data?: DashChartDataPoint[];
    height?: number | string;
    color?: SemanticColor;
    showLegend?: boolean;
    horizontal?: boolean;
}

const semanticToHex: Record<SemanticColor, string> = {
    primary: '#6366f1',
    secondary: '#a855f7',
    accent: '#06b6d4',
    success: '#22c55e',
    warning: '#f59e0b',
    error: '#ef4444',
    info: '#3b82f6',
    neutral: '#64748b',
};

const defaultPalette = [
    '#6366f1',
    '#a855f7',
    '#06b6d4',
    '#22c55e',
    '#f59e0b',
    '#ef4444',
    '#3b82f6',
    '#64748b',
];

function resolveColor(color: string | undefined, index: number): string {
    if (!color) {
        return defaultPalette[index % defaultPalette.length]!;
    }
    if (color in semanticToHex) {
        return semanticToHex[color as SemanticColor];
    }
    return color;
}

export function DashChart({
    type,
    labels = [],
    series = [],
    data = [],
    height = 300,
    color,
    showLegend = true,
    horizontal = false,
}: DashChartProps) {
    const isPie = type === 'pie' || type === 'donut';

    const resolvedColors = useMemo(() => {
        if (isPie && data.length > 0) {
            return data.map((d, i) => resolveColor(d.color as string | undefined, i));
        }
        if (series.length > 0) {
            return series.map((s, i) =>
                resolveColor((s.color as string | undefined) ?? color, i),
            );
        }
        return [resolveColor(color, 0)];
    }, [isPie, data, series, color]);

    const apexSeries = useMemo(() => {
        if (isPie) {
            if (data.length > 0) return data.map((d) => d.value);
            if (series.length > 0 && series[0]) return series[0].data;
            return [];
        }
        if (series.length > 0) {
            return series.map((s) => ({ name: s.name, data: s.data }));
        }
        if (data.length > 0) {
            return [{ name: 'Value', data: data.map((d) => d.value) }];
        }
        return [];
    }, [isPie, series, data]);

    const apexLabels = useMemo(() => {
        if (labels.length > 0) return labels;
        if (data.length > 0) return data.map((d) => d.label);
        return [];
    }, [labels, data]);

    const options = useMemo<ApexOptions>(() => {
        const base: ApexOptions = {
            chart: {
                type,
                toolbar: { show: false },
                zoom: { enabled: false },
                background: 'transparent',
                fontFamily: 'inherit',
                animations: { enabled: true, easing: 'easeout', speed: 250 },
            },
            colors: resolvedColors,
            dataLabels: { enabled: false },
            legend: {
                show: showLegend,
                position: 'bottom',
                fontSize: '12px',
                labels: { colors: 'var(--color-text-muted)' },
            },
            tooltip: { theme: 'dark' },
        };

        // Pie/donut charts choke on cartesian defaults (xaxis, yaxis,
        // grid) — ApexCharts asserts `config.xaxis.convertedCatToNumeric`
        // exists during pie rendering and blows up if we pass `xaxis:
        // undefined`. Keep the config strictly to pie-relevant keys.
        if (isPie) {
            return {
                ...base,
                labels: apexLabels,
                stroke: { width: 0 },
                plotOptions: {
                    pie: {
                        donut: { size: type === 'donut' ? '65%' : '0%' },
                    },
                },
            };
        }

        return {
            ...base,
            stroke: {
                curve: 'smooth',
                width: type === 'line' ? 2 : type === 'area' ? 2 : 0,
            },
            fill:
                type === 'area'
                    ? {
                          type: 'gradient',
                          gradient: {
                              shadeIntensity: 1,
                              opacityFrom: 0.4,
                              opacityTo: 0.05,
                              stops: [0, 100],
                          },
                      }
                    : { opacity: 1 },
            plotOptions: {
                bar: {
                    horizontal,
                    borderRadius: 4,
                    columnWidth: '65%',
                },
            },
            grid: {
                borderColor: 'var(--color-border-subtle)',
                strokeDashArray: 3,
                padding: { left: 8, right: 8 },
            },
            xaxis: {
                categories: apexLabels,
                axisBorder: { show: false },
                axisTicks: { color: 'var(--color-border-subtle)' },
                labels: {
                    style: {
                        colors: 'var(--color-text-subtle)',
                        fontSize: '11px',
                    },
                },
            },
            yaxis: {
                labels: {
                    style: {
                        colors: 'var(--color-text-subtle)',
                        fontSize: '11px',
                    },
                    formatter: (v: number) =>
                        new Intl.NumberFormat().format(Math.round(v)),
                },
            },
        };
    }, [type, resolvedColors, apexLabels, isPie, showLegend, horizontal]);

    return (
        <div className="min-h-[200px]">
            <ReactApexChart
                type={type}
                options={options}
                series={apexSeries as never}
                height={height}
            />
        </div>
    );
}
