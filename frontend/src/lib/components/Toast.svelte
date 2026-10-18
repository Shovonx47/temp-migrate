<script module lang="ts">
	export { toast, type Toast, type ToastType } from '$lib/stores/toast.svelte.js';
</script>

<script lang="ts">
	import { toast } from '$lib/stores/toast.svelte.js';

	const typeConfig = {
		success: {
			bg: 'bg-green-500/15 border-green-500/30',
			icon: 'text-green-400',
			path: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'
		},
		error: {
			bg: 'bg-red-500/15 border-red-500/30',
			icon: 'text-red-400',
			path: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z'
		},
		warning: {
			bg: 'bg-yellow-500/15 border-yellow-500/30',
			icon: 'text-yellow-400',
			path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'
		},
		info: {
			bg: 'bg-blue-500/15 border-blue-500/30',
			icon: 'text-blue-400',
			path: 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z'
		}
	};
</script>

<!-- Toast Container -->
<div class="fixed right-4 bottom-4 z-[100] flex flex-col gap-2">
	{#each toast.toasts as t (t.id)}
		{@const cfg = typeConfig[t.type]}
		<div
			class="animate-slide-up flex w-80 items-start gap-3 rounded-xl border {cfg.bg} p-4 shadow-2xl backdrop-blur-sm"
		>
			<svg
				class="mt-0.5 h-5 w-5 shrink-0 {cfg.icon}"
				fill="none"
				viewBox="0 0 24 24"
				stroke-width="1.5"
				stroke="currentColor"
			>
				<path stroke-linecap="round" stroke-linejoin="round" d={cfg.path} />
			</svg>
			<div class="min-w-0 flex-1">
				<p class="text-sm font-semibold text-gray-100">{t.title}</p>
				{#if t.body}
					<p class="mt-0.5 text-xs text-gray-400">{t.body}</p>
				{/if}
			</div>
			<button
				type="button"
				aria-label="Dismiss notification"
				onclick={() => toast.remove(t.id)}
				class="shrink-0 rounded p-0.5 text-gray-500 transition hover:text-gray-300"
			>
				<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
				</svg>
			</button>
		</div>
	{/each}
</div>
