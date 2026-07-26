import { Head, useForm } from '@inertiajs/react';
import { Button, Input, Pin } from '@artisanpack-ui/react/form';
import { useState, type FormEventHandler } from 'react';

import { AuthLayout } from '../../layouts/AuthLayout';

type ChallengeForm = {
    code: string;
    recovery_code: string;
};

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<ChallengeForm>({
        code: '',
        recovery_code: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post('/two-factor-challenge', {
            onFinish: () => reset('code', 'recovery_code'),
        });
    };

    const toggleMode = () => {
        if (useRecovery) {
            setData('recovery_code', '');
        } else {
            setData('code', '');
        }
        setUseRecovery((previous) => !previous);
    };

    return (
        <AuthLayout
            title="Two-factor authentication"
            description={
                useRecovery
                    ? 'Enter one of your unused recovery codes to continue'
                    : 'Enter the six-digit code from your authenticator app'
            }
        >
            <Head title="Two-factor authentication" />

            <form onSubmit={submit} className="flex flex-col gap-6" noValidate>
                {useRecovery ? (
                    <Input
                        id="recovery_code"
                        name="recovery_code"
                        label="Recovery code"
                        autoComplete="one-time-code"
                        autoFocus
                        required
                        value={data.recovery_code}
                        onChange={(event) => setData('recovery_code', event.target.value)}
                        error={errors.recovery_code}
                    />
                ) : (
                    <div className="flex flex-col gap-2">
                        <span className="text-small text-text-muted">Authentication code</span>
                        <Pin
                            length={6}
                            numeric
                            autoFocus
                            value={data.code}
                            onValueChange={(value) => setData('code', value)}
                            error={errors.code}
                        />
                    </div>
                )}

                <Button type="submit" color="primary" className="w-full" loading={processing}>
                    Log in
                </Button>

                <button
                    type="button"
                    onClick={toggleMode}
                    className="self-center text-small text-text-muted underline hover:text-text"
                >
                    {useRecovery ? 'Use an authentication code' : 'Use a recovery code'}
                </button>
            </form>
        </AuthLayout>
    );
}
