export function NewsletterCard() {
    return (
        <div
            className="rounded-[14px] p-[1px]"
            style={{
                background: 'var(--grad-primary-accent)',
                boxShadow: 'var(--glow-gradient)',
            }}
        >
            <div
                className="rounded-[13px] p-[22px]"
                style={{ background: '#0A0E1B' }}
            >
                <div
                    className="mb-3.5 inline-flex h-[38px] w-[38px] items-center justify-center rounded-[9px]"
                    style={{ background: 'var(--grad-neon)' }}
                    aria-hidden
                >
                    <i
                        className="fa-solid fa-paper-plane text-[15px]"
                        style={{ color: '#04070F' }}
                    />
                </div>
                <h3 className="mb-2 font-display text-[19px] font-bold text-text">
                    Stay in the Loop
                </h3>
                <p className="mb-4 text-[13.5px] leading-[1.55] text-text-muted">
                    Monthly tips, tutorials and package updates — plus a free
                    Quick Start cheat sheet.
                </p>
                <a
                    href="https://artisanpackui.dev"
                    className="flex h-[42px] items-center justify-center gap-2 rounded-[8px] text-[14px] font-bold"
                    style={{
                        background: 'var(--grad-neon)',
                        color: '#04070F',
                    }}
                >
                    Subscribe{' '}
                    <i className="fa-solid fa-arrow-right" aria-hidden />
                </a>
            </div>
        </div>
    );
}

export default NewsletterCard;
