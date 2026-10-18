<script lang="ts">
	import { adminAuth } from '$lib/stores/adminAuth.svelte.js';
	import { goto } from '$app/navigation';

	interface Props {
		onToggleSidebar?: () => void;
		sidebarCollapsed?: boolean;
	}

	let { onToggleSidebar, sidebarCollapsed = false }: Props = $props();

	let serverMenuOpen = $state(false);
	let userMenuOpen = $state(false);
	let searchModalOpen = $state(false);
	let searchQuery = $state('');

	function handleKeydown(e: KeyboardEvent) {
		if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
			e.preventDefault();
			searchModalOpen = !searchModalOpen;
		}
		if (e.key === 'Escape' && searchModalOpen) {
			searchModalOpen = false;
		}
	}

	function handleClickOutside(e: MouseEvent) {
		const target = e.target as HTMLElement;
		if (!target.closest('[data-server-menu]')) {
			serverMenuOpen = false;
		}
		if (!target.closest('[data-user-menu]')) {
			userMenuOpen = false;
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} onclick={handleClickOutside} />

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

		<a href="/superman" class="flex items-center gap-2 select-none">
			<img src="/logo.png" alt="WHITE INFO" class="h-9 w-auto object-contain" />
		</a>
	</div>

	<!-- Center: 7 Live Stat Counter Badges (Matching Filament Topbar Stats) -->
	<div class="hidden xl:flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
		<!-- 1. KYC Pending -->
		<a
			href="/superman/kyc/requests"
			style="background-color: #7c3aed; border: 1px solid #6d28d9; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path d="M4.5 3.75a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V6.75a3 3 0 0 0-3-3h-15Zm4.125 3a2.25 2.25 0 1 1 0 4.5 2.25 2.25 0 0 1 0-4.5Zm-3.75 8.25a3.75 3.75 0 0 1 7.5 0h-7.5Zm9-6.75h5.25a.75.75 0 0 1 0 1.5h-5.25a.75.75 0 0 1 0-1.5Zm0 3.75h5.25a.75.75 0 0 1 0 1.5h-5.25a.75.75 0 0 1 0-1.5Z" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">KYC:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.kyc_pending?.value ?? '1'}</span>
		</a>

		<!-- 2. Today Deposit -->
		<a
			href="/superman/finance/deposits"
			style="background-color: #059669; border: 1px solid #047857; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
				<path fill-rule="evenodd" d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z" clip-rule="evenodd" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">Deposit:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.today_deposit?.value ?? '৳0.00'}</span>
		</a>

		<!-- 3. Today SMS Sent -->
		<a
			href="/superman/system-logs/sms-logs"
			style="background-color: #0284c7; border: 1px solid #0369a1; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0 1 12 2.25c2.43 0 4.817.178 7.152.52 1.978.292 3.348 2.024 3.348 3.97v6.02c0 1.946-1.37 3.678-3.348 3.97a48.901 48.901 0 0 1-3.476.383.39.39 0 0 0-.297.17l-2.755 4.133a.75.75 0 0 1-1.248 0l-2.755-4.133a.39.39 0 0 0-.297-.17 48.9 48.9 0 0 1-3.476-.384c-1.978-.29-3.348-2.024-3.348-3.97V6.741c0-1.946 1.37-3.68 3.348-3.97Z" clip-rule="evenodd" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">SMS:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.today_sms?.value ?? '0'}</span>
		</a>

		<!-- 4. Today Profit -->
		<a
			href="/superman"
			style="background-color: #d97706; border: 1px solid #b45309; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path fill-rule="evenodd" d="M15.22 6.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L18.44 11.5H3.75a.75.75 0 0 1 0-1.5h14.69l-3.22-3.22a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">Profit:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.today_profit?.value ?? '৳0.00'}</span>
		</a>

		<!-- 5. Low Balance -->
		<a
			href="/superman/users/customers"
			style="background-color: #e11d48; border: 1px solid #be123c; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">Low Bal:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.low_balance?.value ?? '4'}</span>
		</a>

		<!-- 6. Open Tickets -->
		<a
			href="/superman"
			style="background-color: #0891b2; border: 1px solid #0e7490; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path fill-rule="evenodd" d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 17.625V6.375Zm15 1.5a.75.75 0 0 0-1.5 0v7.5a.75.75 0 0 0 1.5 0v-7.5Z" clip-rule="evenodd" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">Tickets:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.open_tickets?.value ?? '0'}</span>
		</a>

		<!-- 7. IP Whitelist -->
		<a
			href="/superman/approval-center/ip-whitelists"
			style="background-color: #4f46e5; border: 1px solid #4338ca; color: #ffffff;"
			class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold text-white shadow-xs transition-all hover:brightness-110 active:scale-95"
		>
			<svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
				<path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.75.75 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
			</svg>
			<span class="text-[11px] font-medium opacity-95">IP Whitelist:</span>
			<span class="text-xs font-extrabold">{adminAuth.stats.ip_whitelist?.value ?? '0'}</span>
		</a>
	</div>

	<!-- Right: Global Search + Server Actions + Bell + Superman User Avatar -->
	<div class="flex items-center gap-2 lg:gap-3">
		<!-- Search Button (Ctrl+K) -->
		<button
			type="button"
			onclick={() => (searchModalOpen = true)}
			class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50/70 px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100 transition-colors"
		>
			<svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
			</svg>
			<span class="hidden sm:inline">Search</span>
			<kbd class="hidden sm:inline-block rounded border border-gray-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-gray-400 dark:border-gray-700 dark:bg-gray-900">⌘K</kbd>
		</button>

		<!-- Server Action Dropdown Button (Filament Livewire Server Actions replica) -->
		<div class="relative" data-server-menu>
			<button
				type="button"
				onclick={() => (serverMenuOpen = !serverMenuOpen)}
				class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors"
			>
				<svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.75 5.1a3 3 0 0 1 2.4-1.35h7.7a3 3 0 0 1 2.4 1.35l1.6 3.45a4.5 4.5 0 0 1 .9 2.7" />
				</svg>
				<span class="hidden sm:inline">Server</span>
				<svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
				</svg>
			</button>

			{#if serverMenuOpen}
				<div class="absolute right-0 top-full z-50 mt-1.5 w-56 rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
					<button
						type="button"
						onclick={() => {
							alert('Restart Horizon command dispatched.');
							serverMenuOpen = false;
						}}
						class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
						</svg>
						Restart Horizon
					</button>
					<button
						type="button"
						onclick={() => {
							alert('Application optimization queued.');
							serverMenuOpen = false;
						}}
						class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/30"
					>
						<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
						</svg>
						Optimize Application
					</button>
				</div>
			{/if}
		</div>

		<!-- Notification Bell -->
		<button
			type="button"
			class="relative flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100 transition-colors"
		>
			<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
			</svg>
			<span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900">2</span>
		</button>

		<!-- Superman User Avatar Dropdown -->
		<div class="relative" data-user-menu>
			<button
				type="button"
				onclick={() => (userMenuOpen = !userMenuOpen)}
				class="relative flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-xs font-bold text-white ring-2 ring-emerald-500/80 hover:ring-emerald-600 transition-all cursor-pointer"
			>
				<span>S</span>
				<span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-gray-900"></span>
			</button>

			{#if userMenuOpen}
				<div class="absolute right-0 top-full z-50 mt-2 w-56 rounded-xl border border-gray-200 bg-white py-1 shadow-xl dark:border-gray-700 dark:bg-gray-800">
					<div class="border-b border-gray-100 px-3.5 py-2.5 dark:border-gray-700">
						<p class="text-xs font-bold text-gray-900 dark:text-gray-100">{adminAuth.user?.name ?? 'Superman'}</p>
						<p class="text-[11px] text-gray-500 dark:text-gray-400">{adminAuth.user?.email ?? 'superman@example.com'}</p>
						<span class="mt-1 inline-block rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
							Administrator
						</span>
					</div>

					<a
						href="/superman/system-settings"
						class="flex items-center gap-2 px-3.5 py-2 text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700"
					>
						System Settings
					</a>

					<button
						type="button"
						onclick={async () => {
							await adminAuth.logout();
							goto('/superman/login');
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

<!-- Global Search Modal (Ctrl+K) -->
{#if searchModalOpen}
	<div class="fixed inset-0 z-50 flex items-start justify-center pt-20 bg-gray-950/50 backdrop-blur-xs p-4">
		<div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-4 shadow-2xl dark:border-gray-700 dark:bg-gray-900">
			<div class="flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
				<svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
				</svg>
				<input
					type="text"
					bind:value={searchQuery}
					placeholder="Search customers, sender IDs, batches, settings..."
					class="w-full bg-transparent text-sm text-gray-900 outline-none placeholder:text-gray-400 dark:text-white"
					autofocus
				/>
				<button onclick={() => (searchModalOpen = false)} class="text-xs text-gray-400 hover:text-gray-600">Esc</button>
			</div>

			<div class="mt-3 space-y-1">
				<a href="/superman/users/customers" onclick={() => searchModalOpen = false} class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-xs">
					<span class="font-medium text-gray-800 dark:text-gray-200">Customers Management</span>
					<span class="text-gray-400">/superman/users/customers</span>
				</a>
				<a href="/superman/kyc/requests" onclick={() => searchModalOpen = false} class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-xs">
					<span class="font-medium text-gray-800 dark:text-gray-200">KYC Verification Queue</span>
					<span class="text-gray-400">/superman/kyc/requests</span>
				</a>
				<a href="/superman/approval-center/ip-whitelists" onclick={() => searchModalOpen = false} class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-xs">
					<span class="font-medium text-gray-800 dark:text-gray-200">IP Whitelist Approvals</span>
					<span class="text-gray-400">/superman/approval-center/ip-whitelists</span>
				</a>
				<a href="/superman/finance/deposits" onclick={() => searchModalOpen = false} class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-xs">
					<span class="font-medium text-gray-800 dark:text-gray-200">Deposits & Finance</span>
					<span class="text-gray-400">/superman/finance/deposits</span>
				</a>
			</div>
		</div>
	</div>
{/if}
