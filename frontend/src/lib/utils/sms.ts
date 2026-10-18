/**
 * SMS character counter utility.
 * Performs pure client-side GSM-7 vs Unicode detection, part calculation, and remaining count.
 */

const GSM7_BASIC =
	"@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
const GSM7_EXTENDED = '\f^{}\\[~]|€';

export interface SmsStats {
	length: number;
	encoding: 'GSM-7' | 'Unicode';
	parts: number;
	remaining: number;
	perPartTotal: number;
}

export function computeSmsStats(text: string): SmsStats {
	if (!text) {
		return { length: 0, encoding: 'GSM-7', parts: 0, remaining: 160, perPartTotal: 160 };
	}

	let isUnicode = false;
	let gsmCount = 0;

	for (const ch of [...text]) {
		if (GSM7_EXTENDED.includes(ch)) {
			gsmCount += 2;
		} else if (GSM7_BASIC.includes(ch)) {
			gsmCount += 1;
		} else {
			isUnicode = true;
			break;
		}
	}

	const encoding: 'GSM-7' | 'Unicode' = isUnicode ? 'Unicode' : 'GSM-7';
	const length = isUnicode ? [...text].length : gsmCount;
	const singleLimit = isUnicode ? 70 : 160;
	const concatLimit = isUnicode ? 67 : 153;

	let parts: number;
	let perPartTotal: number;

	if (length === 0) {
		parts = 0;
		perPartTotal = singleLimit;
	} else if (length <= singleLimit) {
		parts = 1;
		perPartTotal = singleLimit;
	} else {
		parts = Math.ceil(length / concatLimit);
		perPartTotal = concatLimit;
	}

	const remaining = parts > 0 ? parts * perPartTotal - length : singleLimit;

	return { length, encoding, parts, remaining, perPartTotal };
}
