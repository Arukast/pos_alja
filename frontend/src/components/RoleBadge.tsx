import { roleLabel, roleTone } from '../lib/labels';
import { Badge } from './ui/Badge';

export function RoleBadge({ role }: { role: string }) {
    return <Badge tone={roleTone(role)}>{roleLabel(role)}</Badge>;
}
