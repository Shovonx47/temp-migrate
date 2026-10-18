<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';

	interface Props {
		title?: string;
		onDepositClick?: () => void;
	}

	let { title = '', onDepositClick }: Props = $props();

	let userMenuOpen = $state(false);

	function formatBalance(balance: number): string {
		return new Intl.NumberFormat('en-US', {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2
		}).format(balance);
	}

	function handleClickOutside(e: MouseEvent) {
		const target = e.target as HTMLElement;
		if (!target.closest('[data-user-menu]')) {
			userMenuOpen = false;
		}
	}
</script>

<svelte:window onclick={handleClickOutside} />

<header
	class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-gray-800 dark:bg-gray-900"
>
	<!-- Left: page title -->
	<div class="flex items-center gap-3">
		{#if title}
			<h1 class="text-base font-semibold text-gray-900 dark:text-gray-100">{title}</h1>
		{/if}
	</div>

	<!-- Right: balance + user menu -->
	<div class="flex items-center gap-3">
		<!-- Balance pill -->
		{#if auth.user}
			<button
				onclick={onDepositClick}
				class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700"
			>
				<svg class="h-4 w-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
				</svg>
				<span class="font-medium text-gray-800 dark:text-gray-200">৳{formatBalance(auth.balance)}</span>
				<span class="text-[11px] text-gray-500">BDT</span>
			</button>

			<!-- User menu -->
			<div class="relative" data-user-menu>
				<button
					onclick={() => (userMenuOpen = !userMenuOpen)}
					class="flex items-center gap-2 rounded-lg p-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
				>
					<div
						class="flex h-7 w-7 items-center justify-center rounded-full bg-teal-600 text-xs font-semibold text-white shadow-sm"
					>
						{auth.user.name[0]?.toUpperCase()}
					</div>
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
					</svg>
				</button>

				{#if userMenuOpen}
					<div
						class="absolute right-0 top-full z-50 mt-2 w-64 animate-slide-up rounded-xl border border-gray-200 bg-white py-2 shadow-xl dark:border-gray-700 dark:bg-gray-800"
					>
						<!-- Profile info -->
						<div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
							<p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{auth.user.name}</p>
							<p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{auth.user.email}</p>
							<div class="mt-2 flex items-center gap-1.5">
								<span class="text-xs text-gray-500">Balance:</span>
								<span class="text-xs font-semibold text-teal-600 dark:text-teal-400">৳{formatBalance(auth.balance)} BDT</span>
							</div>
						</div>

						<!-- Menu items -->
						<div class="py-1">
							<a
								href="/customer/profile"
								class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
								onclick={() => (userMenuOpen = false)}
							>
								<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
								</svg>
								My Profile
							</a>
							<button
								onclick={onDepositClick}
								class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
							>
								<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
								</svg>
								Add Balance
							</button>
						</div>

						<div class="border-t border-gray-100 py-1 dark:border-gray-700">
							<button
								onclick={async () => {
									userMenuOpen = false;
									await auth.logout();
									window.location.href = '/login';
								}}
								class="flex w-full items-center gap-2.5 px-4 py-2 text-sm text-red-600 transition hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-500/10 dark:hover:text-red-300"
							>
								<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
								</svg>
								Sign Out
							</button>
						</div>
					</div>
				{/if}
			</div>
		{/if}
	</div>
</header>
