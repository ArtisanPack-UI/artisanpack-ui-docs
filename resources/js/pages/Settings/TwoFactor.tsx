import { Head, router, useForm } from '@inertiajs/react';
import { Button, Pin } from '@artisanpack-ui/react/form';
import { Card } from '@artisanpack-ui/react/layout';
import { Alert } from '@artisanpack-ui/react/feedback';
import { useState, type FormEventHandler } from 'react';

import { AdminLayout } from '../../layouts/AdminLayout';

interface TwoFactorProps {
    enabled: boolean;
    confirmed: boolean;
    qrCodeSvg: string | null;
    secretKey: string | null;
    recoveryCodes: string[];
    status?: string | null;
}

type ConfirmForm = {
    code: string;
};

export default function TwoFactor({
    enabled,
    confirmed,
    qrCodeSvg,
    secretKey,
    recoveryCodes,
    status,
}: TwoFactorProps) {
    const [showingRecovery, setShowingRecovery] = useState(false);

    const confirmForm = useForm<ConfirmForm>({ code: '' });
    const enableForm = useForm({});
    const disableForm = useForm({});
    const regenerateForm = useForm({});

    const enable: FormEventHandler = (event) => {
        event.preventDefault();
        enableForm.post('/user/two-factor-authentication', {
            preserveScroll: true,
        });
    };

    const confirm: FormEventHandler = (event) => {
        event.preventDefault();
        confirmForm.post('/user/confirmed-two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => confirmForm.reset('code'),
        });
    };

    const disable = () => {
        disableForm.delete('/user/two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => setShowingRecovery(false),
        });
    };

    const regenerate = () => {
        regenerateForm.post('/user/two-factor-recovery-codes', {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['recoveryCodes'] }),
        });
    };

    return (
        <AdminLayout title="Two-factor authentication">
            <Head title="Two-factor authentication" />

            <div className="mx-auto w-full max-w-3xl">
                <header className="mb-8">
                    <p className="text-small text-text-muted">
                        Add an extra layer of security by requiring a code from your authenticator
                        app when you sign in.
                    </p>
                </header>

                {status ? (
                    <Alert color="success" className="mb-6">
                        {status}
                    </Alert>
                ) : null}

                <Card>
                    {!enabled ? (
                        <div className="flex flex-col gap-4">
                            <p className="text-small text-text-muted">
                                Two-factor authentication is not enabled. Enable it to secure your
                                account with an authenticator app.
                            </p>
                            <form onSubmit={enable}>
                                <Button
                                    type="submit"
                                    color="primary"
                                    loading={enableForm.processing}
                                >
                                    Enable two-factor authentication
                                </Button>
                            </form>
                        </div>
                    ) : !confirmed ? (
                        <div className="flex flex-col gap-6">
                            <div>
                                <h2 className="font-display text-h6">Finish setting up</h2>
                                <p className="mt-2 text-small text-text-muted">
                                    Scan the QR code below with your authenticator app, then enter
                                    the six-digit code it generates to finish enabling two-factor
                                    authentication.
                                </p>
                            </div>

                            {qrCodeSvg ? (
                                <div
                                    className="rounded-box bg-surface p-4"
                                    dangerouslySetInnerHTML={{ __html: qrCodeSvg }}
                                />
                            ) : null}

                            {secretKey ? (
                                <div>
                                    <p className="text-small text-text-muted">
                                        Or enter this setup key manually:
                                    </p>
                                    <code className="mt-1 block break-all rounded-box bg-surface px-3 py-2 font-mono text-small">
                                        {secretKey}
                                    </code>
                                </div>
                            ) : null}

                            <form onSubmit={confirm} className="flex flex-col gap-4">
                                <div className="flex flex-col gap-2">
                                    <span className="text-small text-text-muted">
                                        Authentication code
                                    </span>
                                    <Pin
                                        length={6}
                                        numeric
                                        autoFocus
                                        value={confirmForm.data.code}
                                        onValueChange={(value) =>
                                            confirmForm.setData('code', value)
                                        }
                                        error={confirmForm.errors.code}
                                    />
                                </div>

                                <div className="flex flex-wrap gap-3">
                                    <Button
                                        type="submit"
                                        color="primary"
                                        loading={confirmForm.processing}
                                    >
                                        Confirm
                                    </Button>
                                    <Button
                                        type="button"
                                        color="ghost"
                                        onClick={disable}
                                        loading={disableForm.processing}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-6">
                            <Alert color="success">
                                Two-factor authentication is enabled on your account.
                            </Alert>

                            <div>
                                <h2 className="font-display text-h6">Recovery codes</h2>
                                <p className="mt-2 text-small text-text-muted">
                                    Store these recovery codes in a secure password manager. They
                                    can be used to sign in if you lose access to your authenticator
                                    device.
                                </p>

                                <div className="mt-3 flex flex-wrap gap-3">
                                    <Button
                                        type="button"
                                        color="secondary"
                                        onClick={() => setShowingRecovery((previous) => !previous)}
                                    >
                                        {showingRecovery
                                            ? 'Hide recovery codes'
                                            : 'Show recovery codes'}
                                    </Button>
                                    <Button
                                        type="button"
                                        color="ghost"
                                        onClick={regenerate}
                                        loading={regenerateForm.processing}
                                    >
                                        Regenerate codes
                                    </Button>
                                </div>

                                {showingRecovery && recoveryCodes.length > 0 ? (
                                    <ul className="mt-4 grid grid-cols-1 gap-2 rounded-box bg-surface p-4 font-mono text-small sm:grid-cols-2">
                                        {recoveryCodes.map((code) => (
                                            <li key={code}>{code}</li>
                                        ))}
                                    </ul>
                                ) : null}
                            </div>

                            <div>
                                <h2 className="font-display text-h6">Disable</h2>
                                <p className="mt-2 text-small text-text-muted">
                                    Turning off two-factor authentication will remove the
                                    requirement to enter a code when you sign in.
                                </p>
                                <Button
                                    type="button"
                                    color="error"
                                    className="mt-3"
                                    onClick={disable}
                                    loading={disableForm.processing}
                                >
                                    Disable two-factor authentication
                                </Button>
                            </div>
                        </div>
                    )}
                </Card>
            </div>
        </AdminLayout>
    );
}
