import { Head, router } from '@inertiajs/react';
import { Radio } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Alert } from '@artisanpack-ui/react/feedback';
import { useTheme, type ColorScheme } from '@artisanpack-ui/react';
import { useState, type ChangeEvent } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';

interface AppearanceProps {
    theme: ColorScheme;
}

const OPTIONS = [
    { id: 'light', name: 'Light' },
    { id: 'dark', name: 'Dark' },
    { id: 'system', name: 'Match system' },
];

export default function Appearance({ theme }: AppearanceProps) {
    const { setColorScheme } = useTheme();
    const [saved, setSaved] = useState(false);
    const [selected, setSelected] = useState<ColorScheme>(theme);

    const change = (event: ChangeEvent<HTMLInputElement>) => {
        const next = event.target.value as ColorScheme;
        setSelected(next);
        setColorScheme(next);
        router.patch('/dashboard/settings/appearance', { theme: next }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setSaved(true),
        });
    };

    return (
        <AdminLayout title="Appearance">
            <Head title="Appearance" />

            <div className="mx-auto w-full max-w-3xl">
                <header className="mb-8">
                    <p className="text-small text-text-muted">
                        Choose the theme used across ArtisanPack UI.
                    </p>
                </header>

                <Card>
                    <div className="flex flex-col gap-6">
                        <Radio
                            name="theme"
                            label="Theme"
                            options={OPTIONS}
                            value={selected}
                            onChange={change}
                        />

                        {saved ? (
                            <Alert color="success" className="!py-2">
                                Saved.
                            </Alert>
                        ) : null}
                    </div>
                </Card>
            </div>
        </AdminLayout>
    );
}
