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

export interface SharedProps {
    [key: string]: unknown;
    flash: FlashData;
    auth: { user: AuthUser | null };
}

export type PageProps<T extends object = object> = T & SharedProps;
