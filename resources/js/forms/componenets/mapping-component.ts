interface MappingState {
    phone: string | null;
    variables: Record<string, string | null>;
}

interface MappingData {
    source: 'upload';
    options: string[];
    sample: Record<string, string>;
    body: string;
    bodyVariables: string[];
    state: MappingState;
}

interface AlpineMapping {
    source: string;
    options: string[];
    sample: Record<string, string>;
    body: string;
    bodyVariables: string[];
    phone: string | null;
    variableMappings: Record<string, string | null>;
    resolvedBody: string;
    resolvedPhone: string;
    showComponent: boolean;
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
        bodyVariables: initial.bodyVariables,
        phone: initial.state?.phone ?? null,
        variableMappings: initial.state?.variables ?? {},
        resolvedBody: '',
        resolvedPhone: '',

        get showComponent(): boolean {
            return this.source === 'upload';
        },

        init() {
            for (const v of this.bodyVariables) {
                if (!(v in this.variableMappings)) {
                    this.variableMappings[v] = null;
                }
            }
            this.resolve();
        },

        resolve() {
            this.resolvedPhone = this.phone && this.sample[this.phone]
                ? this.sample[this.phone]
                : '—';

            let result = this.body;
            for (const v of this.bodyVariables) {
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
                phone: this.phone,
                variables: { ...this.variableMappings },
            });
        },
    };
}
