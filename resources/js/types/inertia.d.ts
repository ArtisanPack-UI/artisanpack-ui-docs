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

export interface SharedProps {
    [key: string]: unknown;
    flash: FlashData;
}

export type PageProps<T extends object = object> = T & SharedProps;
