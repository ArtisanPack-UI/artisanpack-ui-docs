/**
 * SSR-only stub for `apexcharts` and `react-apexcharts`.
 *
 * The `apexcharts` package touches `window`, `document`, and
 * `getComputedStyle` at module scope, which crashes the Inertia SSR
 * Node process the moment any component in the imported tree references
 * it — including transitively via `@artisanpack-ui/react`'s barrel,
 * which unconditionally pulls its own `Chart` chunk. `vite.config.js`
 * aliases both packages to this file when `isSsrBuild` is true; the
 * client build resolves the real packages as normal.
 */

import type { ComponentType } from 'react';

const ChartStub: ComponentType<Record<string, unknown>> = () => null;

export default ChartStub;
export const ApexOptions = {};
