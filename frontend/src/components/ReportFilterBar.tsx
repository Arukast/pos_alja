import type { Branch } from '../api/types';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';

export interface ReportDraft {
    start: string;
    end: string;
    branch: string;
}

export function ReportFilterBar({
    draft,
    branches,
    loading = false,
    onChange,
    onApply,
}: {
    draft: ReportDraft;
    branches: Branch[];
    loading?: boolean;
    onChange: (draft: ReportDraft) => void;
    onApply: () => void;
}) {
    return (
        <div className="flex flex-wrap items-end gap-3">
            <div className="w-40">
                <Input
                    label="Tanggal mulai"
                    type="date"
                    value={draft.start}
                    onChange={(event) => onChange({ ...draft, start: event.target.value })}
                />
            </div>
            <div className="w-40">
                <Input
                    label="Tanggal akhir"
                    type="date"
                    value={draft.end}
                    onChange={(event) => onChange({ ...draft, end: event.target.value })}
                />
            </div>
            <div className="w-52">
                <Select
                    label="Cabang"
                    value={draft.branch}
                    onChange={(event) => onChange({ ...draft, branch: event.target.value })}
                >
                    <option value="all">Semua cabang</option>
                    {branches.map((branch) => (
                        <option key={branch.id} value={branch.id}>
                            {branch.name}
                        </option>
                    ))}
                </Select>
            </div>
            <Button loading={loading} onClick={onApply}>
                Terapkan
            </Button>
        </div>
    );
}
