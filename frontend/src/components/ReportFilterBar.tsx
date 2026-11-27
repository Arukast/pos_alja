import type { ReportExportFormat } from '../api/queries';
import type { Branch } from '../api/types';
import { periodRange, type Period } from '../lib/dates';
import { Button } from './ui/Button';
import { Input } from './ui/Input';
import { Select } from './ui/Select';

export type ReportPeriodPreset = Period | 'custom';

export interface ReportDraft {
    period: ReportPeriodPreset;
    start: string;
    end: string;
    branch: string;
}

const presetOptions: Array<{ value: ReportPeriodPreset; label: string }> = [
    { value: 'daily', label: 'Harian' },
    { value: 'weekly', label: 'Mingguan' },
    { value: 'monthly', label: 'Bulanan' },
    { value: 'custom', label: 'Kustom' },
];

export function ReportFilterBar({
    draft,
    branches,
    loading = false,
    showBranch = true,
    exporting = null,
    onChange,
    onApply,
    onExport,
}: {
    draft: ReportDraft;
    branches: Branch[];
    loading?: boolean;
    showBranch?: boolean;
    exporting?: ReportExportFormat | null;
    onChange: (draft: ReportDraft) => void;
    onApply: (nextDraft?: ReportDraft) => void;
    onExport?: (format: ReportExportFormat) => void;
}) {
    const selectPreset = (preset: ReportPeriodPreset) => {
        if (preset === 'custom') {
            onChange({ ...draft, period: 'custom' });

            return;
        }

        const range = periodRange(preset);
        const next: ReportDraft = {
            ...draft,
            period: preset,
            start: range.start,
            end: range.end,
        };

        onChange(next);
        onApply(next);
    };

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2" role="group" aria-label="Preset periode">
                {presetOptions.map((option) => (
                    <Button
                        key={option.value}
                        size="sm"
                        variant={draft.period === option.value ? 'primary' : 'secondary'}
                        aria-pressed={draft.period === option.value}
                        onClick={() => selectPreset(option.value)}
                    >
                        {option.label}
                    </Button>
                ))}
            </div>

            <div className="flex flex-wrap items-end gap-3">
                <div className="w-40">
                    <Input
                        label="Tanggal mulai"
                        type="date"
                        value={draft.start}
                        onChange={(event) =>
                            onChange({ ...draft, period: 'custom', start: event.target.value })
                        }
                    />
                </div>
                <div className="w-40">
                    <Input
                        label="Tanggal akhir"
                        type="date"
                        value={draft.end}
                        onChange={(event) =>
                            onChange({ ...draft, period: 'custom', end: event.target.value })
                        }
                    />
                </div>
                {showBranch ? (
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
                ) : null}
                <Button loading={loading} onClick={() => onApply()}>
                    Terapkan
                </Button>
                {onExport ? (
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="secondary"
                            loading={exporting === 'xlsx'}
                            disabled={exporting !== null && exporting !== 'xlsx'}
                            onClick={() => onExport('xlsx')}
                        >
                            Ekspor Excel
                        </Button>
                        <Button
                            variant="secondary"
                            loading={exporting === 'pdf'}
                            disabled={exporting !== null && exporting !== 'pdf'}
                            onClick={() => onExport('pdf')}
                        >
                            Ekspor PDF
                        </Button>
                    </div>
                ) : null}
            </div>
        </div>
    );
}
