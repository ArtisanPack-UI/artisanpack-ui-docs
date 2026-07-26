import { Head } from '@inertiajs/react';
import { GoogleConnectionManager } from '@artisanpack-ui/google/react';
import { GaOverview } from '@artisanpack-ui/analytics-google/react';
import { Card } from '@artisanpack-ui/react/layout';

import { AdminLayout } from '../../layouts/AdminLayout';

export interface GoogleIntegrationPageProps {
    /** GA4 property ID from analytics-google config. Null when unset. */
    propertyId: string | null;
    /** True when a Google OAuth client is configured in env. */
    hasCredentials: boolean;
}

export default function GoogleIntegrationPage({
    propertyId,
    hasCredentials,
}: GoogleIntegrationPageProps) {
    return (
        <AdminLayout title="Integrations · Google">
            <Head title="Integrations · Google" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6">
                <header>
                    <p className="text-small text-text-muted">
                        Connect a Google account so the docs site can pull GA4 reporting data
                        alongside the local analytics stream.
                    </p>
                </header>

                <Card>
                    <h2 className="mb-3 font-display text-h6">OAuth connection</h2>
                    {hasCredentials ? (
                        <GoogleConnectionManager />
                    ) : (
                        <div className="rounded-box border border-border-subtle bg-surface-2 p-4 text-small text-text-muted">
                            <p className="mb-2 font-semibold text-text">
                                Google OAuth client not configured
                            </p>
                            <p>
                                Set{' '}
                                <code className="rounded bg-surface px-1 py-0.5 font-mono text-xsmall">
                                    GOOGLE_CLIENT_ID
                                </code>
                                ,{' '}
                                <code className="rounded bg-surface px-1 py-0.5 font-mono text-xsmall">
                                    GOOGLE_CLIENT_SECRET
                                </code>
                                , and{' '}
                                <code className="rounded bg-surface px-1 py-0.5 font-mono text-xsmall">
                                    GOOGLE_REDIRECT_URI
                                </code>{' '}
                                in <code className="font-mono">.env</code> to enable this panel.
                            </p>
                        </div>
                    )}
                </Card>

                <Card>
                    <h2 className="mb-3 font-display text-h6">GA4 overview</h2>
                    {propertyId ? (
                        <GaOverview initialDays={30} propertyId={propertyId} />
                    ) : (
                        <p className="text-small text-text-muted">
                            Set{' '}
                            <code className="rounded bg-surface px-1 py-0.5 font-mono text-xsmall">
                                GA4_PROPERTY_ID
                            </code>{' '}
                            in <code className="font-mono">.env</code> and connect a Google account
                            to see GA4 reporting here.
                        </p>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}
