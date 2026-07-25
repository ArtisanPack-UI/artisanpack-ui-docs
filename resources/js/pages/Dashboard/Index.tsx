import { Head, Link } from '@inertiajs/react';
import { Sparkline } from '@artisanpack-ui/react/data';
import type { CSSProperties, ReactNode } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';
import { DashChart } from './components/DashChart';

interface PackageRow {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    version: string | null;
    docs_count: number;
    docs_imported_at: string | null;
    last_import_human: string | null;
    registry: 'packagist' | 'npm' | null;
    registry_name: string | null;
    status: 'up_to_date' | 'needs_update' | 'never';
}

interface Totals {
    packages: number;
    documentation: number;
    downloads: number;
    monthly_downloads: number;
    needs_reimport: number;
    up_to_date: number;
    never_imported: number;
    packagist_count: number;
    npm_count: number;
    unlinked_count: number;
    stars: number;
}

interface PackagistStats {
    is_configured: boolean;
    monthly: number;
    daily: number;
    total: number;
    stars: number;
    top_packages: Array<{ name: string; monthly: number; total: number }>;
}

interface NpmStats {
    is_configured: boolean;
    monthly: number;
    weekly: number;
    total: number;
    top_packages: Array<{ name: string; monthly: number; total: number }>;
}

interface AnalyticsPayload {
    is_configured: boolean;
    source: 'local' | 'google';
    source_label: string;
    page_views: number;
    sessions: number;
    users: number;
    avg_session_duration: string;
    bounce_rate: string;
    top_pages: Array<{ page: string; views: number }>;
}

interface SearchConsolePayload {
    clicks: number;
    impressions: number;
    ctr: string;
    position: number;
    top_queries: Array<{
        query: string;
        clicks: number;
        impressions: number;
        ctr: string;
        position: number;
    }>;
}

interface Breakdown {
    label: string;
    value: number;
}

interface TopDocumented {
    name: string;
    slug: string;
    docs_count: number;
}

interface RecentImport {
    name: string;
    slug: string;
    imported_at: string;
    last_import_human: string;
}

interface DashboardPageProps {
    can_view_analytics: boolean;
    totals: Totals;
    packages: PackageRow[];
    packagist: PackagistStats;
    npm: NpmStats;
    analytics: AnalyticsPayload | null;
    search_console: SearchConsolePayload | null;
    registry_breakdown: Breakdown[];
    status_breakdown: Breakdown[];
    top_documented: TopDocumented[];
    recent_imports: RecentImport[];
    docs_distribution: number[];
}

type GradientToken =
    | 'primary-accent'
    | 'primary-secondary'
    | 'secondary-primary'
    | 'secondary-accent'
    | 'accent-primary'
    | 'accent-secondary'
    | 'neon';

function gradientStyle(token: GradientToken): CSSProperties {
    const grad = token === 'neon' ? 'var(--grad-neon)' : `var(--grad-${token})`;

    return {
        background: `linear-gradient(var(--color-surface-2), var(--color-surface-2)) padding-box, ${grad} border-box`,
    };
}

function formatNumber(value: number): string {
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(1)}M`;
    }
    if (value >= 1_000) {
        return `${(value / 1_000).toFixed(1)}K`;
    }
    return value.toLocaleString();
}

interface GradientFrameProps {
    gradient: GradientToken;
    className?: string;
    children: ReactNode;
}

function GradientFrame({ gradient, className = '', children }: GradientFrameProps) {
    return (
        <div
            className={`rounded-box border border-transparent ${className}`.trim()}
            style={gradientStyle(gradient)}
        >
            {children}
        </div>
    );
}

interface StatCardProps {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    accent: 'primary' | 'secondary' | 'accent' | 'warning' | 'success';
    gradient: GradientToken;
    sparklineData?: number[];
    sparklineType?: 'line' | 'area' | 'bar';
}

function StatCard({
    label,
    value,
    hint,
    accent,
    gradient,
    sparklineData,
    sparklineType = 'area',
}: StatCardProps) {
    return (
        <GradientFrame gradient={gradient}>
            <div className="flex flex-col gap-2 p-5">
                <span className="text-xsmall font-medium uppercase tracking-[0.12em] text-text-subtle">
                    {label}
                </span>
                <span
                    className={`font-display text-h3 leading-none tabular-nums text-${accent}`}
                >
                    {value}
                </span>
                {hint ? (
                    <span className="text-xsmall text-text-muted">{hint}</span>
                ) : null}
                {sparklineData && sparklineData.length > 1 ? (
                    <div className="mt-1">
                        <Sparkline
                            data={sparklineData}
                            type={sparklineType}
                            color={accent}
                            height={36}
                            width={220}
                            showTooltip={false}
                        />
                    </div>
                ) : null}
            </div>
        </GradientFrame>
    );
}

interface PanelProps {
    gradient: GradientToken;
    title: string;
    description?: string;
    actions?: ReactNode;
    children: ReactNode;
    padded?: boolean;
    className?: string;
}

function Panel({
    gradient,
    title,
    description,
    actions,
    children,
    padded = true,
    className = '',
}: PanelProps) {
    return (
        <GradientFrame gradient={gradient} className={className}>
            <header className="flex items-start justify-between gap-4 border-b border-border-subtle/60 px-6 py-4">
                <div className="flex flex-col gap-1">
                    <h2 className="font-display text-h6 text-text">{title}</h2>
                    {description ? (
                        <p className="text-xsmall text-text-muted">{description}</p>
                    ) : null}
                </div>
                {actions ? <div className="flex shrink-0 gap-2">{actions}</div> : null}
            </header>
            <div className={padded ? 'p-6' : ''}>{children}</div>
        </GradientFrame>
    );
}

function StatusBadge({ status }: { status: PackageRow['status'] }) {
    if (status === 'up_to_date') {
        return (
            <span className="inline-flex items-center rounded-full bg-success/15 px-2.5 py-0.5 text-xsmall font-medium text-success">
                Up to date
            </span>
        );
    }
    if (status === 'needs_update') {
        return (
            <span className="inline-flex items-center rounded-full bg-warning/15 px-2.5 py-0.5 text-xsmall font-medium text-warning">
                Needs update
            </span>
        );
    }
    return (
        <span className="inline-flex items-center rounded-full bg-error/15 px-2.5 py-0.5 text-xsmall font-medium text-error">
            Never imported
        </span>
    );
}

function EmptyState({ title, description }: { title: string; description: string }) {
    return (
        <div className="flex flex-col items-center justify-center gap-1 py-6 text-center">
            <p className="text-small text-text-muted">{title}</p>
            <p className="text-xsmall text-text-subtle">{description}</p>
        </div>
    );
}

function KeyValueRow({ label, value }: { label: ReactNode; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between border-b border-border-subtle/40 py-2 last:border-b-0">
            <span className="text-small text-text-muted">{label}</span>
            <span className="text-small font-semibold tabular-nums text-text">
                {value}
            </span>
        </div>
    );
}

export default function DashboardIndex(props: DashboardPageProps) {
    const {
        can_view_analytics: canViewAnalytics,
        totals,
        packages,
        packagist,
        npm,
        analytics,
        search_console: searchConsole,
        registry_breakdown: registryBreakdown,
        status_breakdown: statusBreakdown,
        top_documented: topDocumented,
        recent_imports: recentImports,
        docs_distribution: docsDistribution,
    } = props;

    const sparklinePad = docsDistribution.length > 1 ? docsDistribution : [0, 0];
    const statusPad = statusBreakdown.map((b) => b.value);
    const topPackagesForDownloads = [...packagist.top_packages, ...npm.top_packages]
        .sort((a, b) => b.monthly - a.monthly)
        .slice(0, 8);

    return (
        <AdminLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <header className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="font-display text-h3 text-text">Dashboard</h1>
                        <p className="text-small text-text-muted">
                            A live snapshot of every package, download, and visitor across the docs site.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link
                            href="/dashboard/packages"
                            className="rounded-[8px] border border-border-subtle bg-surface px-3 py-1.5 text-small text-text-muted transition hover:border-primary hover:text-text"
                        >
                            Manage packages
                        </Link>
                        <Link
                            href="/dashboard/analytics"
                            className="rounded-[8px] border border-transparent px-3 py-1.5 text-small text-text"
                            style={gradientStyle('neon')}
                        >
                            View analytics
                        </Link>
                    </div>
                </header>

                {/* Row 1: primary stat tiles with sparklines */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Packages"
                        value={totals.packages.toLocaleString()}
                        hint={`${totals.packagist_count} Packagist · ${totals.npm_count} NPM`}
                        accent="primary"
                        gradient="primary-accent"
                        sparklineData={sparklinePad}
                        sparklineType="bar"
                    />
                    <StatCard
                        label="Documentation Pages"
                        value={totals.documentation.toLocaleString()}
                        hint={
                            totals.packages > 0
                                ? `${Math.round(totals.documentation / totals.packages)} avg per package`
                                : 'No packages yet'
                        }
                        accent="secondary"
                        gradient="secondary-accent"
                        sparklineData={sparklinePad}
                        sparklineType="area"
                    />
                    {canViewAnalytics ? (
                        <StatCard
                            label="Total Downloads"
                            value={formatNumber(totals.downloads)}
                            hint={`${formatNumber(totals.monthly_downloads)} this month · Packagist + NPM`}
                            accent="accent"
                            gradient="primary-secondary"
                            sparklineData={
                                topPackagesForDownloads.length > 1
                                    ? topPackagesForDownloads.map((p) => p.monthly)
                                    : [0, 0]
                            }
                            sparklineType="line"
                        />
                    ) : (
                        <StatCard
                            label="Coverage"
                            value={
                                totals.packages > 0
                                    ? `${Math.round((totals.up_to_date / totals.packages) * 100)}%`
                                    : '—'
                            }
                            hint={`${totals.up_to_date} of ${totals.packages} up to date`}
                            accent={totals.needs_reimport > 0 ? 'warning' : 'success'}
                            gradient="primary-secondary"
                        />
                    )}
                    <StatCard
                        label="Needs Re-import"
                        value={totals.needs_reimport.toLocaleString()}
                        hint={
                            totals.needs_reimport > 0
                                ? 'Packages with stale or missing docs'
                                : 'All packages fresh'
                        }
                        accent={totals.needs_reimport > 0 ? 'warning' : 'success'}
                        gradient="secondary-primary"
                        sparklineData={statusPad.length > 1 ? statusPad : [0, 0]}
                        sparklineType="bar"
                    />
                </div>

                {/* Row 2: download-scoped stats (admins only) */}
                {canViewAnalytics ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            label="Packagist Monthly"
                            value={formatNumber(packagist.monthly)}
                            hint={`${formatNumber(packagist.daily)} / day`}
                            accent="primary"
                            gradient="accent-primary"
                        />
                        <StatCard
                            label="NPM Monthly"
                            value={formatNumber(npm.monthly)}
                            hint={`${formatNumber(npm.weekly)} / week`}
                            accent="secondary"
                            gradient="accent-secondary"
                        />
                        <StatCard
                            label="Stars"
                            value={formatNumber(totals.stars)}
                            hint="Across all linked Packagist packages"
                            accent="accent"
                            gradient="primary-accent"
                        />
                        <StatCard
                            label="Coverage"
                            value={
                                totals.packages > 0
                                    ? `${Math.round((totals.up_to_date / totals.packages) * 100)}%`
                                    : '—'
                            }
                            hint={`${totals.up_to_date} of ${totals.packages} up to date`}
                            accent={totals.needs_reimport > 0 ? 'warning' : 'success'}
                            gradient="secondary-accent"
                        />
                    </div>
                ) : null}

                {/* Row 3: monthly downloads (admins) + registry mix */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {canViewAnalytics ? (
                        <Panel
                            gradient="primary-accent"
                            title="Monthly downloads"
                            description="Top-performing packages across Packagist & NPM this month."
                            className="lg:col-span-2"
                        >
                            {topPackagesForDownloads.length > 0 ? (
                                <DashChart
                                    type="bar"
                                    labels={topPackagesForDownloads.map((p) =>
                                        p.name.replace(/^@?artisanpack-ui\//, ''),
                                    )}
                                    series={[
                                        {
                                            name: 'Monthly downloads',
                                            data: topPackagesForDownloads.map((p) => p.monthly),
                                        },
                                    ]}
                                    color="primary"
                                    height={280}
                                    showLegend={false}
                                />
                            ) : (
                                <EmptyState
                                    title="No download data available"
                                    description="Link packages to Packagist or NPM to see monthly download trends."
                                />
                            )}
                        </Panel>
                    ) : null}
                    <Panel
                        gradient="secondary-accent"
                        title="Registry mix"
                        description="How packages are distributed across registries."
                        className={canViewAnalytics ? '' : 'lg:col-span-3'}
                    >
                        {totals.packages > 0 ? (
                            <DashChart
                                type="donut"
                                data={registryBreakdown.map((row, index) => ({
                                    label: row.label,
                                    value: row.value,
                                    color: (
                                        ['primary', 'secondary', 'accent'] as const
                                    )[index % 3],
                                }))}
                                height={280}
                            />
                        ) : (
                            <EmptyState
                                title="No packages linked"
                                description="Add a package to populate the registry breakdown."
                            />
                        )}
                    </Panel>
                </div>

                {/* Row 4: status donut + top documented bar + docs distribution area */}
                <div className="grid gap-6 lg:grid-cols-3">
                    <Panel
                        gradient="accent-primary"
                        title="Documentation status"
                        description="Freshness of imported docs across all packages."
                    >
                        {totals.packages > 0 ? (
                            <DashChart
                                type="donut"
                                data={statusBreakdown.map((row) => ({
                                    label: row.label,
                                    value: row.value,
                                    color:
                                        row.label === 'Up to date'
                                            ? 'success'
                                            : row.label === 'Needs update'
                                              ? 'warning'
                                              : 'error',
                                }))}
                                height={280}
                            />
                        ) : (
                            <EmptyState
                                title="Nothing to score yet"
                                description="Once packages are added, freshness lands here."
                            />
                        )}
                    </Panel>
                    <Panel
                        gradient="accent-secondary"
                        title="Most documented packages"
                        description="Top 6 packages ranked by doc page count."
                    >
                        {topDocumented.length > 0 ? (
                            <DashChart
                                type="bar"
                                labels={topDocumented.map((p) => p.name)}
                                series={[
                                    {
                                        name: 'Docs',
                                        data: topDocumented.map((p) => p.docs_count),
                                    },
                                ]}
                                color="secondary"
                                height={280}
                                showLegend={false}
                                horizontal
                            />
                        ) : (
                            <EmptyState
                                title="No documentation yet"
                                description="Import docs to see the leaderboard."
                            />
                        )}
                    </Panel>
                    <Panel
                        gradient="primary-secondary"
                        title="Docs distribution"
                        description="Doc counts per package, sorted descending."
                    >
                        {sparklinePad.some((v) => v > 0) ? (
                            <DashChart
                                type="area"
                                labels={docsDistribution.map((_, i) => String(i + 1))}
                                series={[
                                    {
                                        name: 'Docs per package',
                                        data: docsDistribution,
                                    },
                                ]}
                                color="accent"
                                height={280}
                                showLegend={false}
                            />
                        ) : (
                            <EmptyState
                                title="No distribution data"
                                description="Docs will populate this chart once imported."
                            />
                        )}
                    </Panel>
                </div>

                {/* Row 5: main packages table + Packagist/NPM columns (admins only) */}
                <div
                    className={`grid gap-6 ${
                        canViewAnalytics ? 'lg:grid-cols-3' : ''
                    }`}
                >
                    <Panel
                        gradient="neon"
                        title="Packages overview"
                        description={`All ${totals.packages} packages with documentation status.`}
                        className={canViewAnalytics ? 'lg:col-span-2' : ''}
                        padded={false}
                        actions={
                            <Link
                                href="/dashboard/packages/create"
                                className="rounded-[8px] border border-transparent px-3 py-1.5 text-xsmall font-medium text-text"
                                style={gradientStyle('primary-accent')}
                            >
                                + Add package
                            </Link>
                        }
                    >
                        <div className="max-h-[520px] overflow-auto">
                            <table className="w-full text-left text-small">
                                <thead className="sticky top-0 z-10 border-b border-border-subtle/60 bg-surface-2 text-xsmall uppercase tracking-[0.08em] text-text-subtle">
                                    <tr>
                                        <th className="px-5 py-3 font-medium">Package</th>
                                        <th className="px-5 py-3 text-center font-medium">Version</th>
                                        <th className="px-5 py-3 text-center font-medium">Docs</th>
                                        <th className="px-5 py-3 text-center font-medium">Last import</th>
                                        <th className="px-5 py-3 text-right font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {packages.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-5 py-8 text-center text-text-muted"
                                            >
                                                No packages yet.{' '}
                                                <Link
                                                    href="/dashboard/packages/create"
                                                    className="text-primary underline-offset-2 hover:underline"
                                                >
                                                    Add your first package
                                                </Link>
                                                .
                                            </td>
                                        </tr>
                                    ) : (
                                        packages.map((pkg) => (
                                            <tr
                                                key={pkg.id}
                                                className="border-b border-border-subtle/40 last:border-b-0 hover:bg-surface/60"
                                            >
                                                <td className="px-5 py-3">
                                                    <div className="flex flex-col gap-0.5">
                                                        <span className="font-medium text-text">
                                                            {pkg.name}
                                                        </span>
                                                        {pkg.registry_name ? (
                                                            <span className="font-mono text-xsmall text-text-subtle">
                                                                {pkg.registry_name}
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                </td>
                                                <td className="px-5 py-3 text-center">
                                                    {pkg.version ? (
                                                        <span className="rounded-full border border-border-subtle px-2 py-0.5 text-xsmall font-mono text-text-muted">
                                                            {pkg.version}
                                                        </span>
                                                    ) : (
                                                        <span className="text-text-subtle">—</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 text-center font-semibold tabular-nums text-text">
                                                    {pkg.docs_count}
                                                </td>
                                                <td className="px-5 py-3 text-center text-text-muted">
                                                    {pkg.last_import_human ?? (
                                                        <span className="text-text-subtle">Never</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 text-right">
                                                    <StatusBadge status={pkg.status} />
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Panel>

                    {canViewAnalytics ? (
                    <div className="flex flex-col gap-6">
                        <Panel
                            gradient="primary-secondary"
                            title="Packagist"
                            description="Composer package downloads"
                        >
                            {packagist.is_configured ? (
                                <div className="flex flex-col gap-1">
                                    <KeyValueRow
                                        label="Monthly"
                                        value={formatNumber(packagist.monthly)}
                                    />
                                    <KeyValueRow
                                        label="Daily"
                                        value={formatNumber(packagist.daily)}
                                    />
                                    <KeyValueRow
                                        label="Total"
                                        value={formatNumber(packagist.total)}
                                    />
                                    <KeyValueRow
                                        label="Stars"
                                        value={formatNumber(packagist.stars)}
                                    />
                                    {packagist.top_packages.length > 0 ? (
                                        <div className="mt-4 flex flex-col gap-2">
                                            <span className="text-xsmall uppercase tracking-[0.1em] text-text-subtle">
                                                Top packages
                                            </span>
                                            {packagist.top_packages.slice(0, 3).map((p) => (
                                                <div
                                                    key={p.name}
                                                    className="flex items-center justify-between text-xsmall"
                                                >
                                                    <span className="truncate font-mono text-text-muted">
                                                        {p.name}
                                                    </span>
                                                    <span className="font-semibold tabular-nums text-text">
                                                        {formatNumber(p.monthly)}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    ) : null}
                                </div>
                            ) : (
                                <EmptyState
                                    title="No Packagist packages linked"
                                    description="Set a package's registry to Packagist to see stats."
                                />
                            )}
                        </Panel>

                        <Panel
                            gradient="primary-accent"
                            title="NPM"
                            description="JavaScript package downloads"
                        >
                            {npm.is_configured ? (
                                <div className="flex flex-col gap-1">
                                    <KeyValueRow
                                        label="Monthly"
                                        value={formatNumber(npm.monthly)}
                                    />
                                    <KeyValueRow
                                        label="Weekly"
                                        value={formatNumber(npm.weekly)}
                                    />
                                    <KeyValueRow
                                        label="Total"
                                        value={formatNumber(npm.total)}
                                    />
                                    {npm.top_packages.length > 0 ? (
                                        <div className="mt-4 flex flex-col gap-2">
                                            <span className="text-xsmall uppercase tracking-[0.1em] text-text-subtle">
                                                Top packages
                                            </span>
                                            {npm.top_packages.slice(0, 3).map((p) => (
                                                <div
                                                    key={p.name}
                                                    className="flex items-center justify-between text-xsmall"
                                                >
                                                    <span className="truncate font-mono text-text-muted">
                                                        {p.name}
                                                    </span>
                                                    <span className="font-semibold tabular-nums text-text">
                                                        {formatNumber(p.monthly)}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    ) : null}
                                </div>
                            ) : (
                                <EmptyState
                                    title="No NPM packages linked"
                                    description="Set a package's registry to NPM to see stats."
                                />
                            )}
                        </Panel>
                    </div>
                    ) : null}
                </div>

                {/* Row 6: analytics (admins) + (optional) search console + recent imports */}
                <div
                    className={`grid gap-6 ${
                        analytics && searchConsole
                            ? 'lg:grid-cols-3'
                            : 'lg:grid-cols-2'
                    }`}
                >
                    {analytics ? (
                    <Panel
                        gradient="secondary-primary"
                        title="Site analytics"
                        description={`Last 30 days · ${analytics.source_label}`}
                    >
                        <div className="flex flex-col gap-1">
                            <KeyValueRow
                                label="Page views"
                                value={formatNumber(analytics.page_views)}
                            />
                            <KeyValueRow
                                label="Sessions"
                                value={formatNumber(analytics.sessions)}
                            />
                            <KeyValueRow
                                label={
                                    analytics.source === 'google' ? 'Users' : 'Visitors'
                                }
                                value={formatNumber(analytics.users)}
                            />
                            <KeyValueRow
                                label="Avg. session"
                                value={analytics.avg_session_duration}
                            />
                            <KeyValueRow
                                label="Bounce rate"
                                value={analytics.bounce_rate}
                            />
                            {analytics.top_pages.length > 0 ? (
                                <div className="mt-4 flex flex-col gap-2">
                                    <span className="text-xsmall uppercase tracking-[0.1em] text-text-subtle">
                                        Top pages
                                    </span>
                                    {analytics.top_pages.slice(0, 3).map((page) => (
                                        <div
                                            key={page.page}
                                            className="flex items-center justify-between gap-3 text-xsmall"
                                        >
                                            <span className="truncate font-mono text-text-muted">
                                                {page.page}
                                            </span>
                                            <span className="font-semibold tabular-nums text-text">
                                                {formatNumber(page.views)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            ) : null}
                        </div>
                    </Panel>
                    ) : null}

                    {searchConsole ? (
                        <Panel
                            gradient="accent-secondary"
                            title="Search Console"
                            description="Last 28 days"
                        >
                            <div className="flex flex-col gap-1">
                                <KeyValueRow
                                    label="Clicks"
                                    value={formatNumber(searchConsole.clicks)}
                                />
                                <KeyValueRow
                                    label="Impressions"
                                    value={formatNumber(searchConsole.impressions)}
                                />
                                <KeyValueRow label="CTR" value={searchConsole.ctr} />
                                <KeyValueRow
                                    label="Avg. position"
                                    value={searchConsole.position.toFixed(1)}
                                />
                                {searchConsole.top_queries.length > 0 ? (
                                    <div className="mt-4 flex flex-col gap-2">
                                        <span className="text-xsmall uppercase tracking-[0.1em] text-text-subtle">
                                            Top queries
                                        </span>
                                        {searchConsole.top_queries.slice(0, 3).map((q) => (
                                            <div
                                                key={q.query}
                                                className="flex items-center justify-between gap-3 text-xsmall"
                                            >
                                                <span className="truncate text-text-muted">
                                                    {q.query}
                                                </span>
                                                <span className="font-semibold tabular-nums text-text">
                                                    {formatNumber(q.clicks)}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                ) : null}
                            </div>
                        </Panel>
                    ) : null}

                    <Panel
                        gradient="primary-accent"
                        title="Recent imports"
                        description="Latest doc imports across the fleet."
                    >
                        {recentImports.length > 0 ? (
                            <ul className="flex flex-col gap-3">
                                {recentImports.map((item) => (
                                    <li
                                        key={item.slug}
                                        className="flex items-start justify-between gap-3 border-b border-border-subtle/40 pb-3 last:border-b-0 last:pb-0"
                                    >
                                        <div className="flex flex-col">
                                            <span className="text-small font-medium text-text">
                                                {item.name}
                                            </span>
                                            <span className="font-mono text-xsmall text-text-subtle">
                                                {item.slug}
                                            </span>
                                        </div>
                                        <span className="whitespace-nowrap text-xsmall text-text-muted">
                                            {item.last_import_human}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                title="No imports yet"
                                description="Trigger an import from any package to see activity."
                            />
                        )}
                    </Panel>
                </div>
            </div>
        </AdminLayout>
    );
}
