import { useState } from 'react';

import { api, type ReportExportFormat, type ReportParams, type ReportType } from '../api/queries';
import type { ToastTone } from '../components/ui/Toast';
import { errorMessage } from '../lib/errors';

export interface ExportNotice {
    message: string;
    tone: ToastTone;
}

export function useReportExport(type: ReportType) {
    const [exporting, setExporting] = useState<ReportExportFormat | null>(null);
    const [notice, setNotice] = useState<ExportNotice | null>(null);

    const clearNotice = () => setNotice(null);

    const exportReport = async (
        format: ReportExportFormat,
        params: ReportParams
    ): Promise<void> => {
        if (exporting !== null) {
            return;
        }

        setExporting(format);
        setNotice(null);

        try {
            const filename = await api.report.exportReport(type, {
                ...params,
                format,
            });

            setNotice({
                message: `Laporan ${filename} berhasil diunduh.`,
                tone: 'success',
            });
        } catch (error) {
            setNotice({
                message: errorMessage(error),
                tone: 'error',
            });
        } finally {
            setExporting(null);
        }
    };

    return {
        exporting,
        notice,
        clearNotice,
        exportReport,
    };
}
