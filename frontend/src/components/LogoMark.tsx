export function LogoMark({
    className,
    decorative = false,
}: {
    className?: string;
    decorative?: boolean;
}) {
    return (
        <svg
            viewBox="0 0 32 32"
            className={className}
            role={decorative ? undefined : 'img'}
            aria-hidden={decorative ? true : undefined}
            aria-label={decorative ? undefined : 'Alja Digital Printing'}
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect width="32" height="32" rx="9" fill="var(--color-brand-primary)" />
            <path
                d="M9 23 L16 9 L23 23"
                stroke="#fff"
                strokeWidth="2.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M12 18 H20"
                stroke="var(--color-brand-accent)"
                strokeWidth="2.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <circle cx="24.5" cy="8.5" r="1.8" fill="var(--color-brand-accent)" />
        </svg>
    );
}
