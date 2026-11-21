export function Sparkline({ points, labels }: { points: number[]; labels: string[] }) {
    if (points.length === 0) {
        return (
            <p className="text-light-muted px-2 py-10 text-center text-sm dark:text-gray-400">
                Belum ada data laba untuk periode ini.
            </p>
        );
    }

    const width = 640;
    const height = 190;
    const padding = 26;
    const min = Math.min(...points, 0);
    const max = Math.max(...points, 0);
    const span = max - min || 1;
    const x = (index: number) =>
        padding + (index * (width - padding * 2)) / Math.max(points.length - 1, 1);
    const y = (value: number) => height - padding - ((value - min) / span) * (height - padding * 2);
    const line = points.map((value, index) => `${index === 0 ? 'M' : 'L'}${x(index)},${y(value)}`).join(' ');
    const area = `${line} L${x(points.length - 1)},${height - padding} L${x(0)},${height - padding} Z`;

    return (
        <div>
            <svg
                viewBox={`0 0 ${width} ${height}`}
                role="img"
                aria-label="Grafik laba bersih"
                className="h-48 w-full"
                preserveAspectRatio="none"
            >
                <defs>
                    <linearGradient id="sparklineFill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stopColor="var(--color-brand-primary)" stopOpacity="0.28" />
                        <stop offset="100%" stopColor="var(--color-brand-primary)" stopOpacity="0" />
                    </linearGradient>
                </defs>
                <line
                    x1={padding}
                    x2={width - padding}
                    y1={y(0)}
                    y2={y(0)}
                    stroke="currentColor"
                    strokeDasharray="4 4"
                    className="text-light-border dark:text-surface-300"
                />
                <path d={area} fill="url(#sparklineFill)" />
                <path
                    d={line}
                    fill="none"
                    stroke="var(--color-brand-primary)"
                    strokeWidth="3"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                {points.map((value, index) => (
                    <circle
                        key={`${labels[index] ?? index}-${value}`}
                        cx={x(index)}
                        cy={y(value)}
                        r="3.5"
                        fill="var(--color-brand-primary)"
                    />
                ))}
            </svg>
            <div
                className="text-light-muted grid gap-1 text-center text-[10px] dark:text-gray-400"
                style={{ gridTemplateColumns: `repeat(${labels.length}, minmax(0, 1fr))` }}
            >
                {labels.map((label) => (
                    <span key={label}>{label}</span>
                ))}
            </div>
        </div>
    );
}
