<script lang="ts">
	import type { Snippet } from 'svelte';

	interface Props {
		open: boolean;
		title?: string;
		description?: string;
		size?: 'sm' | 'md' | 'lg' | 'xl';
		onClose?: () => void;
		children?: Snippet;
	}

	let { open = $bindable(), title, description, size = 'md', onClose, children }: Props = $props();

	const sizeClasses = {
		sm: 'max-w-sm',
		md: 'max-w-md',
		lg: 'max-w-lg',
		xl: 'max-w-xl'
	};

	function handleClose() {
		open = false;
		onClose?.();
	}

	function handleBackdrop(e: MouseEvent) {
		if ((e.target as HTMLElement).dataset.backdrop) handleClose();
	}

	function handleKeydown(e: KeyboardEvent) {
		if (e.key === 'Escape') handleClose();
	}
</script>

<svelte:window onkeydown={handleKeydown} />

{#if open}
	<!-- Backdrop -->
	<div
		class="fixed inset-0 z-50 flex items-center justify-center p-4"
		data-backdrop="true"
		onclick={handleBackdrop}
		onkeydown={(e) => {
			if (e.key === 'Escape') handleClose();
		}}
		tabindex="-1"
		role="dialog"
		aria-modal="true"
	>
		<div
			class="absolute inset-0 bg-black/40 backdrop-blur-sm"
			data-backdrop="true"
			aria-hidden="true"
		></div>

		<!-- Panel -->
		<div
			class="relative z-10 w-full {sizeClasses[size]} animate-slide-up rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
		>
			<!-- Header -->
			{#if title}
				<div class="flex items-start justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
					<div>
						<h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{title}</h2>
						{#if description}
							<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{description}</p>
						{/if}
					</div>
					<button
						type="button"
						aria-label="Close modal"
						onclick={handleClose}
						class="ml-4 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-300"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
						</svg>
					</button>
				</div>
			{/if}

			<!-- Content -->
			<div class="px-6 py-4">
				{#if children}
					{@render children()}
				{/if}
			</div>
		</div>
	</div>
{/if}
