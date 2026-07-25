/**
 * Ambient module declaration for the React components shipped by
 * `artisanpack-ui/analytics-google`. Aliased at the Vite layer to the
 * vendor `resources/js/react/` source; only the props we consume from
 * the app are typed here so `tsc --noEmit` stays decoupled from the
 * vendor tree.
 */

import type { ComponentType, JSX } from 'react';

export interface GaOverviewData {
    range: { start_date: string; end_date: string; days: number };
    totals: {
        activeUsers?: number;
        sessions?: number;
        screenPageViews?: number;
        averageSessionDuration?: number;
        [key: string]: number | undefined;
    };
    series?: Array<{ date: string; [metric: string]: string | number }>;
    property_id?: string | null;
}

export interface GaOverviewProps {
    initialDays?: number;
    propertyId?: string | null;
    baseUrl?: string;
    fetchImpl?: ( input: RequestInfo | URL, init?: RequestInit ) => Promise<Response>;
}

export interface GaTopContentProps {
    initialDays?: number;
    initialLimit?: number;
    propertyId?: string | null;
    baseUrl?: string;
    fetchImpl?: ( input: RequestInfo | URL, init?: RequestInit ) => Promise<Response>;
}

export interface Ga4SnippetProps {
    measurementId: string;
    configOptions?: Record<string, unknown>;
    respectConsent?: boolean;
    consentCategory?: string;
}

export const GaOverview: ( props: GaOverviewProps ) => JSX.Element;
export const GaTopContent: ( props: GaTopContentProps ) => JSX.Element;
export const Ga4Snippet: ComponentType<Ga4SnippetProps>;
