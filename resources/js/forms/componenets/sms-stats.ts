interface AlpineSmsStats {
    text: string;
    remaining: number;
    total: number;
    parts: number;
    encoding: 'GSM-7' | 'Unicode';
    $cleanup?: (callback: () => void) => void;

    init(): void;
    recompute(): void;
}

const GSM_BASIC = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';
const GSM_EXTENDED = '\f^{}\\[~]|€';

const LIMITS = {
    'GSM-7': { single: 160, concat: 153 },
    'Unicode': { single: 70, concat: 67 },
} as const;

const BODY_SELECTOR = '[data-sms-body]';
const BROADCAST_EVENT = 'sms-body-input';
const HOOK_FLAG = '_smsBroadcastHooked';

export default function smsStats(): AlpineSmsStats {
    return {
        text: '',
        remaining: LIMITS['GSM-7'].single,
        total: LIMITS['GSM-7'].single,
        parts: 0,
        encoding: 'GSM-7',

        init() {
            const handler = (e: Event) => {
                this.text = (e as CustomEvent<{ value: string }>).detail.value;
                this.recompute();
            };
            window.addEventListener(BROADCAST_EVENT, handler);

            const attach = () => {
                const textarea = document.querySelector<HTMLTextAreaElement>(BODY_SELECTOR);
                if (!textarea) {
                    requestAnimationFrame(attach);
                    return;
                }

                this.text = textarea.value || '';
                this.recompute();

                const hooked = textarea as HTMLTextAreaElement & { [HOOK_FLAG]?: boolean };
                if (hooked[HOOK_FLAG]) return;
                hooked[HOOK_FLAG] = true;
                textarea.addEventListener('input', () => {
                    window.dispatchEvent(new CustomEvent(BROADCAST_EVENT, {
                        detail: { value: textarea.value },
                    }));
                });
            };
            attach();

            this.$cleanup?.(() => window.removeEventListener(BROADCAST_EVENT, handler));
        },

        recompute() {
            let isUnicode = false;
            let count = 0;

            for (const ch of this.text) {
                if (GSM_EXTENDED.includes(ch)) {
                    count += 2;
                } else if (GSM_BASIC.includes(ch)) {
                    count += 1;
                } else {
                    isUnicode = true;
                    break;
                }
            }

            this.encoding = isUnicode ? 'Unicode' : 'GSM-7';
            const { single, concat } = LIMITS[this.encoding];
            const length = isUnicode ? [...this.text].length : count;

            if (length === 0) {
                this.parts = 0;
                this.total = single;
                this.remaining = single;
            } else if (length <= single) {
                this.parts = 1;
                this.total = single;
                this.remaining = single - length;
            } else {
                this.parts = Math.ceil(length / concat);
                this.total = concat;
                this.remaining = (this.parts * concat) - length;
            }
        },
    };
}
