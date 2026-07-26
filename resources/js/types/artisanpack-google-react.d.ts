/**
 * Ambient module declaration for the React connection-manager component
 * shipped by `artisanpack-ui/google`. Aliased at the Vite layer; typed
 * here to keep the vendor tree out of the app's type-check graph.
 */

import type { JSX } from 'react';

export interface GoogleConnectionStatus {
    connected: boolean;
    email?: string | null;
    scopes?: string[];
    connected_at?: string | null;
    expires_at?: string | null;
    needs_reauthorization?: boolean;
    [key: string]: unknown;
}

export interface GoogleConnectionLabels {
    heading?: string;
    connectedHeading?: string;
    disconnectedHeading?: string;
    connectButton?: string;
    disconnectButton?: string;
    reauthorizeButton?: string;
    loading?: string;
    disconnecting?: string;
    disconnected?: string;
    connectedAs?: string;
    scopes?: string;
    lastConnected?: string;
    error?: string;
    [key: string]: string | undefined;
}

export interface GoogleConnectionManagerProps {
    statusUrl?: string;
    csrfToken?: string | null;
    labels?: Partial<GoogleConnectionLabels>;
    onDisconnected?: () => void;
    onStatus?: (status: GoogleConnectionStatus) => void;
    connectUrl?: string;
    disconnectUrl?: string;
    reauthorizeUrl?: string;
    className?: string;
}

export const GoogleConnectionManager: (props: GoogleConnectionManagerProps) => JSX.Element;
