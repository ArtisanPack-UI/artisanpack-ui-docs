import '@inertiajs/core';

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: SharedProps;
        flashDataType: FlashData;
    }
}

export interface FlashData {
    success?: string;
    error?: string;
    info?: string;
    warning?: string;
}

export type UserRole = 'admin' | 'editor';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
}

export interface SeoData {
    title: string;
    description: string | null;
    canonical: string;
    robots: string;
    openGraph: Record<string, string>;
    twitter: Record<string, string>;
    hreflang: Array<{ hreflang: string; href: string }>;
    jsonLd: Array<Record<string, unknown>>;
}

export interface ReconsentPolicy {
    version: string;
    regulation: string | null;
    url: string;
}

export interface SharedProps {
    [key: string]: unknown;
    flash: FlashData;
    auth: { user: AuthUser | null };
    seo: SeoData;
    reconsent: ReconsentPolicy | null;
}

export type PageProps<T extends object = object> = T & SharedProps;
