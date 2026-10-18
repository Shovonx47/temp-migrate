import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';

interface PhoneTagsInputData {
    state: string[] | null;
    statePath: string;
    options: Record<string, unknown>;
    locale: string | null;
    splitKeys: string[];
    intlTelInputSelectedCountryCookieName: string;
    suggestions?: string[];
}

interface PhoneTagsInputComponent {
    state: string[];
    statePath: string;
    current: string;
    options: Record<string, unknown>;
    splitKeys: string[];
    suggestions: string[];
    iti: ReturnType<typeof intlTelInput> | null;
    input: HTMLInputElement | null;
    intlTelInputSelectedCountryCookieName: string;
    $refs: Record<string, HTMLElement>;
    $wire: { set(name: string, value: unknown, live?: boolean): void };
    $nextTick(cb?: () => void): Promise<void>;
    init(): Promise<void>;
    destroy(): void;
    initOptions(): void;
    commit(): boolean;
    syncState(): void;
    removeTag(index: number): void;
    toggleSuggestion(number: string): void;
}

function setCookie(name: string, value: string): void {
    if (!name) return;
    const maxAge = 60 * 60 * 24 * 365; // 1 year
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
}

function getCookie(name: string): string {
    if (!name) return '';
    const search = `${encodeURIComponent(name)}=`;
    const parts = document.cookie.split(';');
    for (let raw of parts) {
        raw = raw.trim();
        if (raw.indexOf(search) === 0) {
            return decodeURIComponent(raw.substring(search.length));
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
        splitKeys: data.splitKeys ?? ['Enter', 'Tab', ','],
        suggestions: Array.isArray(data.suggestions) ? data.suggestions : [],
        iti: null as ReturnType<typeof intlTelInput> | null,
        input: null as HTMLInputElement | null,
        intlTelInputSelectedCountryCookieName: data.intlTelInputSelectedCountryCookieName,

        async init(this: PhoneTagsInputComponent) {
            // Wait for next tick/frame so intl-tel-input attaches after layout settles
            await new Promise((resolve) => setTimeout(resolve, 150));

            const input = this.$refs.input as HTMLInputElement | undefined;
            if (!input) {
                return;
            }

            this.input = input;
            this.initOptions();
            this.iti = intlTelInput(input, this.options);

            if (!Array.isArray(this.state)) {
                this.state = [];
            }

            input.addEventListener('keydown', (event: KeyboardEvent) => {
                // Backspace on empty input removes the last tag
                if (event.key === 'Backspace' && input.value === '' && this.state.length > 0) {
                    this.removeTag(this.state.length - 1);
                    return;
                }

                if (!this.splitKeys.includes(event.key)) {
                    return;
                }

                // If pressing Tab and the input is empty, allow natural tab navigation to next field
                if (event.key === 'Tab' && input.value.trim() === '') {
                    return;
                }

                event.preventDefault();
                this.commit();
            });

            input.addEventListener('input', () => {
                input.classList.remove('fi-invalid');
            });

            input.addEventListener('blur', () => {
                if (input.value.trim() !== '') {
                    this.commit();
                }
            });

            input.addEventListener('paste', (event: ClipboardEvent) => {
                const pasted = event.clipboardData?.getData('text') ?? '';

                // Detect multi-number paste: contains a newline, comma, semicolon, or tab
                if (!/[,\t;\n\r]/.test(pasted)) {
                    // Single value — let browser paste normally, committed via Enter or blur
                    return;
                }

                event.preventDefault();

                // Split on any combination of \r\n, \r, \n, comma, semicolon, or tab
                const lines = pasted.split(/\r\n|\r|\n|[,\t;]/);
                const added: string[] = [];
                const currentState = Array.isArray(this.state) ? this.state : [];

                for (const raw of lines) {
                    // Strip whitespace and tabs
                    const number = raw.replace(/\t/g, '').trim();

                    if (number === '') {
                        continue;
                    }

                    // Must have at least 5 digits to filter out spreadsheet headers (e.g. "Phone", "Mobile", "ID")
                    const digits = number.replace(/\D/g, '');
                    if (digits.length < 5) {
                        continue;
                    }

                    // Skip exact duplicates already in state or already added this paste
                    if (currentState.includes(number) || added.includes(number)) {
                        continue;
                    }

                    // Try intl-tel-input first (handles international format numbers like +8801713...)
                    if (this.iti) {
                        this.iti.setNumber(number);
                        if (this.iti.isValidNumber()) {
                            const e164 = this.iti.getNumber() as string;
                            if (e164 && !currentState.includes(e164) && !added.includes(e164)) {
                                added.push(e164);
                            }
                            this.iti.setNumber('');
                            input.value = '';
                            this.current = '';
                            continue;
                        }

                        this.iti.setNumber('');
                    }

                    input.value = '';
                    this.current = '';

                    // Accept plausible phone numbers (digits, optional leading +, spaces, dashes, parens)
                    if (/^[+]?[\d\s\-()./]+$/.test(number)) {
                        added.push(number);
                    }
                }

                if (added.length > 0) {
                    this.state = [...currentState, ...added];
                    this.syncState();
                }
            });

            // Catch any unsent input on form submit
            const livewireForm = input.closest('form');
            livewireForm?.addEventListener(
                'submit',
                () => {
                    if (input.value.trim() !== '') {
                        this.commit();
                    }
                },
                { capture: true },
            );

            input.addEventListener('countrychange', () => {
                if (!this.iti) return;
                const country = this.iti.getSelectedCountryData();
                if (country?.iso2 && this.intlTelInputSelectedCountryCookieName) {
                    setCookie(this.intlTelInputSelectedCountryCookieName, country.iso2.toUpperCase());
                }
            });
        },

        destroy(this: PhoneTagsInputComponent) {
            if (this.iti) {
                this.iti.destroy();
                this.iti = null;
            }
        },

        initOptions(this: PhoneTagsInputComponent) {
            const cookie = getCookie(this.intlTelInputSelectedCountryCookieName);
            const initialCountry = cookie
                ? cookie.toLowerCase()
                : (typeof this.options.initialCountry === 'string' && this.options.initialCountry
                    ? this.options.initialCountry
                    : 'auto');

            this.options = {
                ...this.options,
                initialCountry,
                geoIpLookup: (success: (country: string) => void) => {
                    if (cookie) {
                        success(cookie.toLowerCase());
                    } else if (initialCountry && initialCountry !== 'auto') {
                        success(initialCountry.toLowerCase());
                    } else {
                        success('us');
                    }
                },
            };
        },

        commit(this: PhoneTagsInputComponent): boolean {
            if (!this.iti || !this.input) {
                return false;
            }

            const raw = this.input.value.trim();
            if (raw === '') {
                this.input.classList.remove('fi-invalid');
                return false;
            }

            let numberToAdd = '';

            // intl-tel-input reports validity against the currently selected country.
            if (this.iti.isValidNumber()) {
                numberToAdd = (this.iti.getNumber() as string) || raw;
            } else {
                // If intl-tel-input failed, check if it's a valid local format
                const digits = raw.replace(/\D/g, '');
                if (digits.length >= 7 && /^[+]?[\d\s\-()./]+$/.test(raw)) {
                    numberToAdd = raw;
                } else {
                    this.input.classList.add('fi-invalid');
                    return false;
                }
            }

            this.input.classList.remove('fi-invalid');

            if (!Array.isArray(this.state)) {
                this.state = [];
            }

            if (numberToAdd && !this.state.includes(numberToAdd)) {
                this.state = [...this.state, numberToAdd];
                this.syncState();
            }

            this.iti.setNumber('');
            this.input.value = '';
            this.current = '';
            return true;
        },

        syncState(this: PhoneTagsInputComponent) {
            if (this.$wire && typeof this.$wire.set === 'function') {
                this.$wire.set(this.statePath, this.state);
            }
        },

        removeTag(this: PhoneTagsInputComponent, index: number) {
            if (!Array.isArray(this.state)) {
                this.state = [];
                return;
            }
            this.state = this.state.filter((_, i) => i !== index);
            this.syncState();
        },

        toggleSuggestion(this: PhoneTagsInputComponent, number: string) {
            if (!Array.isArray(this.state)) {
                this.state = [];
            }
            const clean = number.trim();
            const index = this.state.indexOf(clean);
            if (index > -1) {
                this.state = this.state.filter((_, i) => i !== index);
            } else {
                this.state = [...this.state, clean];
            }
            this.syncState();
        },
    };
}
