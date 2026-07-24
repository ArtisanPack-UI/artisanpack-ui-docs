import { Head, useForm } from '@inertiajs/react';

import { PublicLayout } from '@/layouts/PublicLayout';

interface VerifyProps {
    token: string;
    data_request: {
        id: number;
        type: string;
        status: string;
        created_at: string | null;
    };
    expired: boolean;
    status_message: string | null;
}

const TYPE_LABELS: Record<string, string> = {
    access: 'access request',
    export: 'data export',
    deletion: 'deletion request',
    rectification: 'correction request',
};

function buttonLabel(status: string): string {
    switch (status) {
        case 'pending':
            return 'Confirm my identity';
        case 'processing':
        case 'completed':
            return 'Already verified';
        case 'rejected':
            return 'Request rejected';
        case 'failed':
            return 'Verification failed';
        default:
            return 'Unavailable';
    }
}

export default function Verify({ token, data_request, expired, status_message }: VerifyProps) {
    const form = useForm({});

    const submit = () => {
        form.post(`/verify/${token}`, { preserveScroll: true });
    };

    return (
        <PublicLayout>
            <Head title="Verify Your Request" />

            <div className="mx-auto flex w-full max-w-[560px] flex-col gap-6 px-6 py-16">
                <header className="flex flex-col gap-2">
                    <p className="text-small uppercase tracking-[0.14em] text-text-subtle">
                        Privacy
                    </p>
                    <h1 className="font-display text-3xl font-semibold tracking-tight">
                        Verify your request
                    </h1>
                    <p className="text-small text-text-muted">
                        This confirms your identity for the {TYPE_LABELS[data_request.type] ?? data_request.type} you
                        submitted. Once verified we'll start processing it.
                    </p>
                </header>

                {status_message ? (
                    <div className="rounded-[12px] border border-secondary/30 bg-secondary/10 p-4 text-small text-text">
                        {status_message}
                    </div>
                ) : null}

                {expired ? (
                    <div className="rounded-[12px] border border-error/40 bg-error/10 p-4 text-small text-text">
                        This verification link has expired. Please submit a new request.
                    </div>
                ) : (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            submit();
                        }}
                        className="flex flex-col gap-4 rounded-[14px] border border-border-subtle bg-surface-2/70 p-6"
                    >
                        <dl className="grid grid-cols-2 gap-y-2 text-small">
                            <dt className="text-text-subtle">Request type</dt>
                            <dd className="capitalize">{data_request.type}</dd>
                            <dt className="text-text-subtle">Status</dt>
                            <dd className="capitalize">{data_request.status}</dd>
                        </dl>

                        <button
                            type="submit"
                            disabled={form.processing || data_request.status !== 'pending'}
                            className="inline-flex h-10 items-center justify-center rounded-[8px] bg-primary px-4 text-small font-medium text-base transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {buttonLabel(data_request.status)}
                        </button>
                    </form>
                )}
            </div>
        </PublicLayout>
    );
}
