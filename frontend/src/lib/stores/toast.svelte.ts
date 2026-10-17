/**
 * Toast notification store using Svelte 5 runes.
 */

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
	id: string;
	type: ToastType;
	title: string;
	body?: string;
	duration?: number;
}

function createToastStore() {
	let toasts = $state<Toast[]>([]);

	function add(toast: Omit<Toast, 'id'>) {
		const id = crypto.randomUUID();
		toasts = [...toasts, { ...toast, id }];
		setTimeout(() => remove(id), toast.duration ?? 4000);
	}

	function remove(id: string) {
		toasts = toasts.filter((t) => t.id !== id);
	}

	return {
		get toasts() {
			return toasts;
		},
		success: (title: string, body?: string) => add({ type: 'success', title, body }),
		error: (title: string, body?: string) => add({ type: 'error', title, body }),
		warning: (title: string, body?: string) => add({ type: 'warning', title, body }),
		info: (title: string, body?: string) => add({ type: 'info', title, body }),
		remove
	};
}

export const toast = createToastStore();
