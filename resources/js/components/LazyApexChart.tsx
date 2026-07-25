import { useEffect, useState } from 'react';
import type { ComponentType } from 'react';
import type { ApexOptions } from 'apexcharts';

/**
 * Client-only wrapper around `react-apexcharts`. ApexCharts touches
 * `window` / `document` / `getComputedStyle` at module load time, which
 * crashes the Inertia SSR bundle. Deferring the import to `useEffect`
 * keeps the component tree renderable on the server (returns null,
 * matching the pre-hydration DOM) and only pulls the chart library in
 * once we're safely in the browser.
 */

export interface LazyApexChartProps {
    type: NonNullable<ApexOptions['chart']>['type'];
    options: ApexOptions;
    series: unknown;
    height?: number | string;
    width?: number | string;
}

type ReactApexChartComponent = ComponentType<LazyApexChartProps>;

export function LazyApexChart(props: LazyApexChartProps) {
    const [Chart, setChart] = useState<ReactApexChartComponent | null>(null);

    useEffect(() => {
        let cancelled = false;
        import('react-apexcharts').then((mod) => {
            if (!cancelled) {
                setChart(() => mod.default as ReactApexChartComponent);
            }
        });
        return () => {
            cancelled = true;
        };
    }, []);

    if (!Chart) {
        return null;
    }

    return <Chart {...props} />;
}
