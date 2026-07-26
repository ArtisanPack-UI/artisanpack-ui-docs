import { useMemo } from 'react';
import type { ApexOptions } from 'apexcharts';
import { LazyApexChart } from '@/components/LazyApexChart';

export interface ChartPoint {
    date: string;
    pageviews: number;
    visitors: number;
}

export interface PageViewsChartProps {
    data: ChartPoint[];
    height?: number;
}

export function PageViewsChart({ data, height = 300 }: PageViewsChartProps) {
    const options = useMemo<ApexOptions>(
        () => ({
            chart: {
                type: 'area',
                toolbar: { show: false },
                zoom: { enabled: false },
                background: 'transparent',
                fontFamily: 'inherit',
                animations: { enabled: true, easing: 'easeout', speed: 250 },
            },
            colors: ['#7c3aed', '#22d3ee'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 100],
                },
            },
            grid: {
                borderColor: 'var(--color-border-subtle)',
                strokeDashArray: 3,
                padding: { left: 8, right: 8 },
            },
            xaxis: {
                type: 'datetime',
                categories: data.map((d) => d.date),
                axisBorder: { show: false },
                axisTicks: { color: 'var(--color-border-subtle)' },
                labels: {
                    style: { colors: 'var(--color-text-subtle)', fontSize: '11px' },
                    datetimeFormatter: {
                        year: 'yyyy',
                        month: "MMM 'yy",
                        day: 'MMM d',
                        hour: 'HH:mm',
                    },
                },
            },
            yaxis: {
                labels: {
                    style: { colors: 'var(--color-text-subtle)', fontSize: '11px' },
                    formatter: (value: number) => new Intl.NumberFormat().format(Math.round(value)),
                },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '12px',
                labels: { colors: 'var(--color-text-muted)' },
                markers: {
                    // ApexCharts v3 typings reject `size` on markers,
                    // but the DaisyUI-adjacent build the app pins to
                    // does accept it. Cast to unknown so the shape
                    // survives without silencing every other prop.
                    size: 6,
                } as unknown as ApexOptions['legend'] extends infer L
                    ? L extends { markers?: infer M }
                        ? M
                        : never
                    : never,
            },
            tooltip: {
                theme: 'dark',
                x: { format: 'MMM d, yyyy' },
            },
        }),
        [data],
    );

    const series = useMemo(
        () => [
            {
                name: 'Pageviews',
                data: data.map((d) => d.pageviews),
            },
            {
                name: 'Visitors',
                data: data.map((d) => d.visitors),
            },
        ],
        [data],
    );

    if (data.length === 0) {
        return (
            <p className="py-8 text-center text-small text-text-muted">
                No pageviews yet for the selected range.
            </p>
        );
    }

    return (
        <div className="min-h-[300px]">
            <LazyApexChart type="area" options={options} series={series} height={height} />
        </div>
    );
}
