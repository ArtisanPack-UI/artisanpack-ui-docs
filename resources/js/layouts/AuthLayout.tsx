import { Link } from '@inertiajs/react';
import { Card } from '@artisanpack-ui/react/layout';
import type { ReactNode } from 'react';

export interface AuthLayoutProps {
    children: ReactNode;
    title?: string;
    description?: string;
}

export function AuthLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="ap-aurora min-h-screen bg-base text-text">
            <div className="flex min-h-screen flex-col items-center justify-center px-6 py-12">
                <Link href="/" className="mb-8 font-display text-xl font-semibold tracking-tight">
                    ArtisanPack UI
                </Link>

                <Card className="w-full max-w-md">
                    {title ? (
                        <header className="mb-6 text-center">
                            <h1 className="font-display text-h4">{title}</h1>
                            {description ? (
                                <p className="mt-2 text-small text-text-muted">{description}</p>
                            ) : null}
                        </header>
                    ) : null}

                    {children}
                </Card>

                <p className="mt-6 text-xsmall text-text-subtle">
                    © {new Date().getFullYear()} ArtisanPack UI
                </p>
            </div>
        </div>
    );
}

export default AuthLayout;
