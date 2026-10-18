<script lang="ts">
	import { spAuth } from '$lib/stores/spAuth.svelte.js';
	import { goto } from '$app/navigation';

	interface Props {
		onToggleSidebar?: () => void;
		sidebarCollapsed?: boolean;
	}

	let { onToggleSidebar, sidebarCollapsed = false }: Props = $props();

	let userMenuOpen = $state(false);

	function handleClickOutside(e: MouseEvent) {
		const target = e.target as HTMLElement;
		if (!target.closest('[data-sp-menu]')) {
			userMenuOpen = false;
		}
	}
</script>

<svelte:window onclick={handleClickOutside} />

<header
	class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b border-gray-200/80 bg-white/95 px-3 lg:px-4 backdrop-blur-md transition-colors dark:border-gray-800 dark:bg-gray-900/95"
>
	<!-- Left section: Collapse Button + Logo -->
	<div class="flex items-center gap-2.5">
		<button
			type="button"
			onclick={onToggleSidebar}
			title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
			class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100 transition-colors"
		>
			<svg
				class="h-5 w-5 transition-transform duration-200 {sidebarCollapsed ? 'rotate-180' : ''}"
				fill="none"
				viewBox="0 0 24 24"
				stroke-width="2"
				stroke="currentColor"
			>
				<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
			</svg>
		</button>

		<a href="/sp" class="flex items-center gap-2 select-none">
			<img src="/logo.png" alt="WHITE INFO" class="h-9 w-auto object-contain" />
			<span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
				S P RESELLER
			</span>
		</a>
	</div>

	<!-- Right: Balance Button Pill + User Avatar -->
	<div class="flex items-center gap-3">
		<!-- SP Balance Button (Replicating Filament balance-button livewire component) -->
		<a
			href="/sp/wallet"
			class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 shadow-2xs transition hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
		>
			<svg class="h-4 w-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
			</svg>
			<span>Balance: ৳{spAuth.balance.toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
		</a>

		<!-- SP User Avatar Dropdown -->
		<div class="relative" data-sp-menu>
			<button
				type="button"
				onclick={() => (userMenuOpen = !userMenuOpen)}
				class="relative flex h-8 w-8 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white ring-2 ring-amber-400/80 hover:ring-amber-500 transition-all cursor-pointer"
			>
				<span>{spAuth.user?.name[0]?.toUpperCase() ?? 'P'}</span>
				<span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-gray-900"></span>
			</button>

			{#if userMenuOpen}
				<div class="absolute right-0 top-full z-50 mt-2 w-56 rounded-xl border border-gray-200 bg-white py-1 shadow-xl dark:border-gray-700 dark:bg-gray-800">
					<div class="border-b border-gray-100 px-3.5 py-2.5 dark:border-gray-700">
						<p class="text-xs font-bold text-gray-900 dark:text-gray-100">{spAuth.user?.name ?? 'Partner Reseller'}</p>
						<p class="text-[11px] text-gray-500 dark:text-gray-400">{spAuth.user?.email ?? 'sp@example.com'}</p>
						<span class="mt-1 inline-block rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
							S P Reseller
						</span>
					</div>

					<a
						href="/sp/wallet"
						class="flex items-center gap-2 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
					>
						Wallet & Distribution
					</a>

					<button
						type="button"
						onclick={async () => {
							await spAuth.logout();
							goto('/sp/login');
						}}
						class="flex w-full items-center gap-2 px-3.5 py-2 text-left text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30"
					>
						Sign out
					</button>
				</div>
			{/if}
		</div>
	</div>
</header>
