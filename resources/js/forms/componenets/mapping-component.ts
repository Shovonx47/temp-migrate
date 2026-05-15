interface MappingState {
    phone: string | null;
    variables: Record<string, string | null>;
}

interface MappingData {
    source: 'upload' | 'group' | 'manual';
    options: string[];
    sample: Record<string, string>;
    body: string;
    variables: string[];
    state: MappingState;
}

interface AlpineMapping {
    source: string;
    options: string[];
    sample: Record<string, string>;
    body: string;
    variables: string[];
    phone: string | null;
    variableMappings: Record<string, string | null>;
    resolvedBody: string;
    resolvedPhone: string;
    showComponent: boolean;
    isGroup: boolean;
    $dispatch?(event: string, detail?: any): void;

    init(): void;
    resolve(): void;
    sync(): void;
}

const MAX_PREVIEW_LEN = 120;

export default function mappingComponent(initial: MappingData): AlpineMapping {
    return {
        source: initial.source,
        options: initial.options,
        sample: initial.sample,
        body: initial.body,
        variables: initial.variables,
        phone: initial.state?.phone ?? (initial.source === 'group' ? 'phone' : null),
        variableMappings: initial.state?.variables ?? {},
        resolvedBody: '',
        resolvedPhone: '',

        get showComponent(): boolean {
            return this.source === 'upload' || this.source === 'group';
        },

        get isGroup(): boolean {
            return this.source === 'group';
        },

        init() {
            for (const v of this.variables) {
                if (!(v in this.variableMappings)) {
                    this.variableMappings[v] = null;
                }
            }
            this.resolve();
        },

        resolve() {
            // For groups, phone is always from the contact's phone field
            // For upload, resolve from the mapped column
            if (this.isGroup) {
                this.resolvedPhone = this.sample['phone'] || '—';
            } else {
                this.resolvedPhone = this.phone && this.sample[this.phone]
                    ? this.sample[this.phone]
                    : '—';
            }

            // Resolve body by substituting variables with sample data
            let result = this.body;
            for (const v of this.variables) {
                const col = this.variableMappings[v];
                const value = col && this.sample[col] ? this.sample[col] : `{${v}}`;
                result = result.replaceAll(`{${v}}`, value);
            }
            this.resolvedBody = result.length > MAX_PREVIEW_LEN
                ? result.slice(0, MAX_PREVIEW_LEN) + '...'
                : result;

            this.sync();
        },

        sync() {
            this.$dispatch?.('mapping-updated', {
                phone: this.isGroup ? 'phone' : this.phone,
                variables: { ...this.variableMappings },
            });
        },
    };
}
