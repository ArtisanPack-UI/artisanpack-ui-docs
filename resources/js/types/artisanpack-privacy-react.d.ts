import type { ComponentType, ReactNode } from 'react';

export interface PolicyReconsentPayload {
    version: string;
    regulation?: string | null;
    url?: string;
}

export interface CookieBannerProps {
    className?: string;
    header?: ReactNode;
    description?: ReactNode;
    footer?: ReactNode;
    acceptLabel?: string;
    rejectLabel?: string;
    customiseLabel?: string;
    saveLabel?: string;
    onClose?: () => void;
}

export interface PolicyReconsentBannerProps {
    policy: PolicyReconsentPayload | null;
    className?: string;
    buttonClassName?: string;
    header?: ReactNode;
    description?: ReactNode;
    footer?: ReactNode;
    labels?: {
        title?: string;
        description?: string;
        review?: string;
        accept?: string;
        dismiss?: string;
    };
    policyUrl?: string;
    reconsentUrl?: string;
    onAccept?: (version: string) => void;
    onDismiss?: () => void;
}

export const CookieBanner: ComponentType<CookieBannerProps>;
export const PolicyReconsentBanner: ComponentType<PolicyReconsentBannerProps>;

export interface ConsentMap {
    [category: string]: boolean;
}

export interface ConsentCategoryConfig {
    name?: string;
    description?: string;
    required?: boolean;
}

export interface ConsentState {
    categories: Record<string, ConsentCategoryConfig>;
    consents: ConsentMap;
    _policy_version?: string;
}

export interface UseConsentOptions {
    endpoint?: string;
    skipInitialLoad?: boolean;
}

export interface UseConsentResult {
    state: ConsentState | null;
    loading: boolean;
    error: Error | null;
    hasConsent: (category: string) => boolean;
    setConsent: (category: string, granted: boolean) => Promise<ConsentState>;
    setConsents: (consents: ConsentMap) => Promise<ConsentState>;
    reload: () => Promise<ConsentState>;
}

export function useConsent(options?: UseConsentOptions): UseConsentResult;

interface BaseAdminProps {
    className?: string;
    endpoint?: string;
    heading?: ReactNode;
    fetchImpl?: typeof fetch;
    csrfToken?: string;
    perPage?: number;
}

export const AdminConsentManager: ComponentType<BaseAdminProps>;
export const AdminDataRequestManager: ComponentType<BaseAdminProps>;
export const AdminComplianceReport: ComponentType<Omit<BaseAdminProps, 'csrfToken' | 'perPage'>>;
export interface AdminBreachManagerProps extends Omit<BaseAdminProps, 'csrfToken'> {
    detailUrlBuilder?: (id: number) => string;
    reportUrl?: string;
}
export const AdminBreachManager: ComponentType<AdminBreachManagerProps>;
export interface AdminBreachReportFormProps extends Omit<BaseAdminProps, 'perPage'> {
    onSubmitted?: (breach: { id: number }) => void;
}
export const AdminBreachReportForm: ComponentType<AdminBreachReportFormProps>;
export interface AdminBreachDetailProps extends Omit<BaseAdminProps, 'perPage'> {
    breachId: number;
}
export const AdminBreachDetail: ComponentType<AdminBreachDetailProps>;
