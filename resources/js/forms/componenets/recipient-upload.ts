// resources/js/filament/components/RecipientUpload.ts

import Papa from 'papaparse';
import ExcelJS from 'exceljs';

interface ParsedData {
    headers: string[];
    rows: string[][];
    hasHeaderRow: boolean;
}

interface AlpineComponent {
    file: File | null;
    fileName: string;
    isDragging: boolean;
    isProcessing: boolean;
    error: string | null;
    headers: string[];
    rows: string[][];
    hasHeaderRow: boolean;
    previewRows: string[][];
    state: any; // Filament wire state

    init(): void;
    handleDrop(e: DragEvent): void;
    handleDragOver(e: DragEvent): void;
    handleDragLeave(e: DragEvent): void;
    handleFileInput(e: Event): void;
    processFile(file: File): Promise<void>;
    parseCSV(file: File): Promise<ParsedData>;
    parseExcel(file: File): Promise<ParsedData>;
    detectHeaderRow(firstRow: string[]): boolean;
    isBangladeshiPhone(value: string): boolean;
    reset(): void;
    syncState(): void;
}

const BD_PHONE_REGEX = /^(?:\+?88)?01[3-9]\d{8}$/;
const PREVIEW_LIMIT = 5;

export default function recipientUpload(initialState: any = {}): AlpineComponent {
    return {
        file: null,
        fileName: '',
        isDragging: false,
        isProcessing: false,
        error: null,
        headers: [],
        rows: [],
        hasHeaderRow: false,
        previewRows: [],
        state: initialState,

        init() {
            // Restore from state if user navigates back to step
            if (this.state?.headers && this.state?.rows) {
                this.headers = this.state.headers;
                this.rows = this.state.rows;
                this.hasHeaderRow = this.state.hasHeaderRow ?? false;
                this.fileName = this.state.fileName ?? '';
                this.previewRows = this.rows.slice(0, PREVIEW_LIMIT);
            }
        },

        handleDragOver(e: DragEvent) {
            e.preventDefault();
            this.isDragging = true;
        },

        handleDragLeave(e: DragEvent) {
            e.preventDefault();
            this.isDragging = false;
        },

        handleDrop(e: DragEvent) {
            e.preventDefault();
            this.isDragging = false;
            const file = e.dataTransfer?.files?.[0];
            if (file) this.processFile(file);
        },

        handleFileInput(e: Event) {
            const target = e.target as HTMLInputElement;
            const file = target.files?.[0];
            if (file) this.processFile(file);
        },

        async processFile(file: File) {
            this.error = null;
            this.isProcessing = true;
            this.file = file;
            this.fileName = file.name;

            try {
                const ext = file.name.split('.').pop()?.toLowerCase();
                let parsed: ParsedData;

                if (ext === 'csv' || ext === 'txt') {
                    parsed = await this.parseCSV(file);
                } else if (ext === 'xlsx' || ext === 'xls') {
                    parsed = await this.parseExcel(file);
                } else {
                    throw new Error('Unsupported file type. Use CSV or Excel.');
                }

                if (parsed.rows.length === 0) {
                    throw new Error('File is empty.');
                }

                this.headers = parsed.headers;
                this.rows = parsed.rows;
                this.hasHeaderRow = parsed.hasHeaderRow;
                this.previewRows = parsed.rows.slice(0, PREVIEW_LIMIT);

                this.syncState();
            } catch (err: any) {
                this.error = err.message || 'Failed to parse file.';
                this.reset();
            } finally {
                this.isProcessing = false;
            }
        },

        parseCSV(file: File): Promise<ParsedData> {
            return new Promise((resolve, reject) => {
                Papa.parse<string[]>(file, {
                    skipEmptyLines: true,
                    complete: (results) => {
                        if (results.errors.length > 0) {
                            reject(new Error(results.errors[0].message));
                            return;
                        }

                        const allRows = results.data as string[][];
                        if (allRows.length === 0) {
                            resolve({ headers: [], rows: [], hasHeaderRow: false });
                            return;
                        }

                        const hasHeader = this.detectHeaderRow(allRows[0]);
                        const headers = hasHeader
                            ? allRows[0].map((h) => String(h).trim())
                            : allRows[0].map((_, i) => `Column ${i + 1}`);
                        const rows = hasHeader ? allRows.slice(1) : allRows;

                        resolve({ headers, rows, hasHeaderRow: hasHeader });
                    },
                    error: (err) => reject(err),
                });
            });
        },

        async parseExcel(file: File): Promise<ParsedData> {
            const buffer = await file.arrayBuffer();
            const workbook = new ExcelJS.Workbook();
            await workbook.xlsx.load(buffer);

            const worksheet = workbook.worksheets[0];
            if (!worksheet) {
                throw new Error('No sheet found in file.');
            }

            const allRows: string[][] = [];
            worksheet.eachRow({ includeEmpty: false }, (row) => {
                const values = row.values as any[];
                // ExcelJS row.values is 1-indexed (index 0 is empty)
                const cells = values.slice(1).map((v) => {
                    if (v === null || v === undefined) return '';
                    if (typeof v === 'object') {
                        // Handle rich text, hyperlinks, formulas
                        if ('text' in v) return String(v.text);
                        if ('result' in v) return String(v.result ?? '');
                        if ('richText' in v) {
                            return v.richText.map((rt: any) => rt.text).join('');
                        }
                        return String(v);
                    }
                    return String(v).trim();
                });
                allRows.push(cells);
            });

            if (allRows.length === 0) {
                return { headers: [], rows: [], hasHeaderRow: false };
            }

            const hasHeader = this.detectHeaderRow(allRows[0]);
            const headers = hasHeader
                ? allRows[0]
                : allRows[0].map((_, i) => `Column ${i + 1}`);
            const rows = hasHeader ? allRows.slice(1) : allRows;

            return { headers, rows, hasHeaderRow: hasHeader };
        },

        detectHeaderRow(firstRow: string[]): boolean {
            // If first row has any cell that looks like a BD phone, treat as data row
            const looksLikePhone = firstRow.some((cell) =>
                this.isBangladeshiPhone(String(cell))
            );
            return !looksLikePhone;
        },

        isBangladeshiPhone(value: string): boolean {
            const cleaned = String(value).replace(/[\s-]/g, '');
            return BD_PHONE_REGEX.test(cleaned);
        },

        reset() {
            this.file = null;
            this.fileName = '';
            this.headers = [];
            this.rows = [];
            this.hasHeaderRow = false;
            this.previewRows = [];
            this.syncState();
        },

        syncState() {
            // Push parsed data into Filament form state
            this.state = {
                fileName: this.fileName,
                headers: this.headers,
                rows: this.rows,
                hasHeaderRow: this.hasHeaderRow,
                rowCount: this.rows.length,
            };
            // Trigger Livewire/Alpine state sync
            this.$dispatch('recipient-upload-changed', this.state);
        },
    };
}
