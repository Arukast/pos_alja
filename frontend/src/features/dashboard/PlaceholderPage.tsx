import { Card, CardBody } from '../../components/ui/Card';
import { StatCard } from '../../components/ui/StatCard';

export function PlaceholderPage({ title, description }: { title: string; description: string }) {
    return (
        <div className="space-y-6">
            <header>
                <p className="text-brand-primary text-xs font-semibold uppercase tracking-[0.25em]">
                    Workspace
                </p>
                <h1 className="mt-1 text-2xl font-bold">{title}</h1>
                <p className="text-light-muted mt-1 max-w-2xl text-sm dark:text-gray-400">
                    {description}
                </p>
            </header>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Awaiting data" value="-" hint="API wiring lands in the next task" />
                <StatCard label="Branch scope" value="Active" hint="Manager and staff are branch-bound" />
                <StatCard label="Access" value="Protected" hint="Sanctum session required" />
                <StatCard label="Realtime" value="Ready" hint="Private branch channel" />
            </div>

            <Card>
                <CardBody>
                    <p className="text-light-muted text-sm dark:text-gray-400">
                        This screen is part of the shell parity pass. The data views and actions are
                        implemented in the read and write phases of the plan.
                    </p>
                </CardBody>
            </Card>
        </div>
    );
}
