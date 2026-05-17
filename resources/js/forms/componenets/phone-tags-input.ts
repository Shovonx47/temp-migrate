import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';

interface PhoneTagsInputData {
    state: string[] | null;
    statePath: string;
    options: Record<string, unknown>;
    locale: string | null;
    splitKeys: string[];
    intlTelInputSelectedCountryCookieName: string;
}

interface PhoneTagsInputComponent {
    state: string[];
    statePath: string;
    current: string;
    options: Record<string, unknown>;
    splitKeys: string[];
    iti: any;
    input: HTMLInputElement | null;
    intlTelInputSelectedCountryCookieName: string;
    $refs: Record<string, HTMLElement>;
    $wire: { set(name: string, value: unknown, live?: boolean): void };
    $nextTick(cb?: () => void): Promise<void>;
    init(): Promise<void>;
    initOptions(): void;
    commit(): boolean;
    syncState(): void;
    removeTag(index: number): void;
}

function setCookie(name: string, value: string): void {
    document.cookie = `${name}=${value};`;
}

function getCookie(name: string): string {
    const search = `${name}=`;
    const parts = document.cookie.split(';');
    for (let raw of parts) {
        raw = raw.trim();
        if (raw.indexOf(search) === 0) {
            return raw.substring(search.length);
        }
    }
    return '';
}

export default function phoneTagsInput(data: PhoneTagsInputData) {
    return {
        state: Array.isArray(data.state) ? data.state : [],
        statePath: data.statePath,
        current: '',
        options: data.options ?? {},
        splitKeys: data.splitKeys ?? ['Enter', ','],
        iti: null,
        input: null,
        intlTelInputSelectedCountryCookieName: data.intlTelInputSelectedCountryCookieName,

        async init(this: PhoneTagsInputComponent) {
            // Wait for the next frame so intl-tel-input attaches after layout settles
            // (mirrors the upstream PhoneInput component's delay for repeater/modal contexts).
            await new Promise((resolve) => setTimeout(resolve, 150));

            this.initOptions();
            this.input = this.$refs.input as HTMLInputElement;
            this.iti = intlTelInput(this.input, this.options);

            this.input.addEventListener('keydown', (event: KeyboardEvent) => {
                if (!this.splitKeys.includes(event.key)) {
                    return;
                }
                event.preventDefault();
                this.commit();
            });

            this.input.addEventListener('blur', () => {
                if (this.input && this.input.value.trim() !== '') {
                    this.commit();
                }
            });

            this.input.addEventListener('paste', (event: ClipboardEvent) => {
                const pasted = event.clipboardData?.getData('text') ?? '';
                if (!/[,;\n]/.test(pasted)) {
                    return;
                }
                event.preventDefault();
                const numbers = pasted
                    .split(/[,;\n]/)
                    .map((p: string) => p.trim())
                    .filter((p: string) => p.length > 0);
                for (const number of numbers) {
                    this.iti.setNumber(number);
                    this.commit();
                }
            });

            // Catch any unsent input on form submit (e.g. user hits the Save button
            // while a number is still in the typing field).
            const livewireForm = this.input.closest('form');
            livewireForm?.addEventListener(
                'submit',
                () => {
                    if (this.input && this.input.value.trim() !== '') {
                        this.commit();
                    }
                },
                { capture: true },
            );

            this.input.addEventListener('countrychange', () => {
                const country = this.iti.getSelectedCountryData();
                if (country.iso2) {
                    setCookie(this.intlTelInputSelectedCountryCookieName, country.iso2.toUpperCase());
                }
            });
        },

        initOptions(this: PhoneTagsInputComponent) {
            const cookie = getCookie(this.intlTelInputSelectedCountryCookieName);
            this.options = {
                ...this.options,
                initialCountry: cookie ? cookie.toLowerCase() : (this.options.initialCountry ?? 'auto'),
                geoIpLookup: (success: (country: string) => void) => {
                    success(cookie || 'us');
                },
            };
        },

        commit(this: PhoneTagsInputComponent): boolean {
            if (!this.iti || !this.input) {
                return false;
            }

            // intl-tel-input reports validity against the currently selected country.
            // We only accept fully-valid numbers to avoid storing junk.
            if (!this.iti.isValidNumber()) {
                if (this.input.value.trim() === '') {
                    return false;
                }
                this.input.classList.add('fi-invalid');
                return false;
            }

            this.input.classList.remove('fi-invalid');

            const e164 = this.iti.getNumber() as string;
            if (!e164) {
                return false;
            }

            if (!this.state.includes(e164)) {
                this.state = [...this.state, e164];
                this.syncState();
            }

            this.iti.setNumber('');
            this.input.value = '';
            this.current = '';
            return true;
        },

        syncState(this: PhoneTagsInputComponent) {
            // $entangle alone doesn't always propagate array mutations back to
            // Livewire — push the array explicitly so the server-side state matches.
            this.$wire.set(this.statePath, this.state, false);
        },

        removeTag(this: PhoneTagsInputComponent, index: number) {
            this.state = this.state.filter((_, i) => i !== index);
            this.syncState();
        },
    };
}
