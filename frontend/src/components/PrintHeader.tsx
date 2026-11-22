export function PrintHeader({ className = '' }: { className?: string }) {
    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <span className="flex gap-1" aria-hidden="true">
                <span className="bg-brand-primary h-2 w-2 rounded-[1.5px]" />
                <span className="bg-brand-secondary h-2 w-2 rounded-[1.5px]" />
                <span className="bg-brand-accent h-2 w-2 rounded-[1.5px]" />
                <span className="bg-brand-dark h-2 w-2 rounded-[1.5px]" />
            </span>
            <span className="text-xs font-bold uppercase tracking-[0.2em]">
                Alja Digital Printing
            </span>
        </div>
    );
}
