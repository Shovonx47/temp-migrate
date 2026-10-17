<script lang="ts">
	import { auth } from '$lib/stores/auth.svelte.js';
	import { api } from '$lib/api.js';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';

	interface DashboardStats {
		messages_today: number;
		successful_today: number;
		failed_today: number;
		cost_today: number;
		delivery_rate: number;
		contacts_total: number;
	}

	interface RecentTransaction {
		id: number;
		type: string;
		direction: string;
		amount: string;
		balance_after: string;
		created_at: string;
		description?: string;
	}

	let stats = $state<DashboardStats | null>(null);
	let transactions = $state<RecentTransaction[]>([]);
	let loadingStats = $state(true);
	let loadingTxns = $state(true);

	async function loadStats() {
		loadingStats = true;
		try {
			const data = await api.get<{ data: DashboardStats }>('/api/v1/customer/dashboard/stats');
			stats = data.data;
		} catch {
			stats = {
				messages_today: 0,
				successful_today: 0,
				failed_today: 0,
				cost_today: 0,
				delivery_rate: 0,
				contacts_total: 0
			};
		} finally {
			loadingStats = false;
		}
	}

	async function loadTransactions() {
		loadingTxns = true;
		try {
			const data = await api.get<{ data: RecentTransaction[] }>(
				'/api/v1/customer/transactions?per_page=5'
			);
			transactions = data.data;
		} catch {
			transactions = [];
		} finally {
			loadingTxns = false;
		}
	}

	onMount(() => {
		loadStats();
		loadTransactions();
	});

	function formatMoney(val: number | string): string {
		return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(Number(val));
	}

	function formatDate(iso: string): string {
		return new Date(iso).toLocaleDateString('en-GB', {
			day: '2-digit',
			month: 'short',
			hour: '2-digit',
			minute: '2-digit'
		});
	}

	const statCards = $derived([
		{
			label: 'Current Balance',
			value: `৳${formatMoney(auth.balance)}`,
			sub: 'BDT Available',
			icon: 'banknotes',
			color: 'text-primary-400',
			bg: 'bg-primary-500/10'
		},
		{
			label: 'Messages Today',
			value: stats ? String(stats.messages_today) : '—',
			sub: 'Sent today',
			icon: 'paper-airplane',
			color: 'text-blue-400',
			bg: 'bg-blue-500/10'
		},
		{
			label: 'Delivery Rate',
			value: stats ? `${stats.delivery_rate}%` : '—',
			sub: `${stats?.successful_today ?? 0} successful`,
			icon: 'check-badge',
			color:
				(stats?.delivery_rate ?? 0) >= 90
					? 'text-green-400'
					: (stats?.delivery_rate ?? 0) >= 70
						? 'text-yellow-400'
						: 'text-red-400',
			bg:
				(stats?.delivery_rate ?? 0) >= 90
					? 'bg-green-500/10'
					: (stats?.delivery_rate ?? 0) >= 70
						? 'bg-yellow-500/10'
						: 'bg-red-500/10'
		},
		{
			label: 'Failed Today',
			value: stats ? String(stats.failed_today) : '—',
			sub: 'Send & DLR failures',
			icon: 'x-circle',
			color: (stats?.failed_today ?? 0) > 0 ? 'text-red-400' : 'text-green-400',
			bg: (stats?.failed_today ?? 0) > 0 ? 'bg-red-500/10' : 'bg-green-500/10'
		},
		{
			label: "Today's Spend",
			value: stats ? `৳${formatMoney(stats.cost_today)}` : '—',
			sub: 'BDT charged',
			icon: 'currency-dollar',
			color: 'text-yellow-400',
			bg: 'bg-yellow-500/10'
		},
		{
			label: 'Total Contacts',
			value: stats ? String(stats.contacts_total) : '—',
			sub: 'In your address book',
			icon: 'users',
			color: 'text-violet-400',
			bg: 'bg-violet-500/10'
		}
	]);

	const iconPaths: Record<string, string> = {
		banknotes: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />`,
		'paper-airplane': `<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />`,
		'check-badge': `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />`,
		'x-circle': `<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
		'currency-dollar': `<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />`,
		users: `<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />`
	};
</script>

<svelte:head>
	<title>Dashboard – SMS Portal</title>
	<meta name="description" content="Your SMS Portal customer dashboard." />
</svelte:head>

<div class="space-y-6">
	<!-- Welcome banner -->
	<div
		class="relative overflow-hidden rounded-2xl border border-teal-100 bg-gradient-to-r from-teal-50/70 via-white to-white p-6 shadow-sm dark:border-gray-800 dark:bg-gradient-to-r dark:from-teal-950/40 dark:to-gray-900"
	>
		<div class="relative">
			<h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
				Good day, {auth.user?.name?.split(' ')[0]} 👋
			</h2>
			<p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
				Here's an overview of your SMS activity today.
			</p>
		</div>
		<!-- Quick actions -->
		<div class="relative mt-4 flex flex-wrap gap-2">
			<a
				href="/customer/messages/quick"
				id="btn-quick-msg"
				class="flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
				</svg>
				Quick Message
			</a>
			<a
				href="/customer/messages/bulk"
				id="btn-bulk-msg"
				class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			>
				<svg class="h-4 w-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
				</svg>
				Bulk Message
			</a>
			<a
				href="/customer/deposits"
				id="btn-deposit"
				class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			>
				<svg class="h-4 w-4 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
				Add Balance
			</a>
		</div>
	</div>

	<!-- Stats grid -->
	<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
		{#each statCards as card (card.label)}
			<div
				class="rounded-xl border border-gray-200/90 bg-white p-4 shadow-sm transition hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900"
			>
				{#if loadingStats}
					<div class="space-y-2">
						<div class="h-8 w-8 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
						<div class="h-4 w-16 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
						<div class="h-6 w-24 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
					</div>
				{:else}
					<div class="flex items-center justify-between">
						<div class="rounded-lg {card.bg} p-2">
							<svg
								class="h-4 w-4 {card.color}"
								fill="none"
								viewBox="0 0 24 24"
								stroke-width="1.5"
								stroke="currentColor"
							>
								{@html iconPaths[card.icon] ?? ''}
							</svg>
						</div>
					</div>
					<p class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">{card.value}</p>
					<p class="mt-0.5 text-xs font-medium text-gray-500">{card.label}</p>
					<p class="mt-0.5 text-[11px] text-gray-400">{card.sub}</p>
				{/if}
			</div>
		{/each}
	</div>

	<!-- Recent Transactions -->
	<div class="rounded-xl border border-gray-200/90 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
			<h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200">Recent Transactions</h3>
			<a href="/customer/transactions" class="text-xs font-semibold text-teal-600 transition hover:text-teal-700 dark:text-teal-400">
				View all →
			</a>
		</div>
		<div class="divide-y divide-gray-100 dark:divide-gray-800">
			{#if loadingTxns}
				{#each { length: 5 } as _, i (i)}
					<div class="flex items-center justify-between px-4 py-3">
						<div class="space-y-1.5">
							<div class="h-3 w-32 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
							<div class="h-3 w-20 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
						</div>
						<div class="h-4 w-16 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
					</div>
				{/each}
			{:else if transactions.length === 0}
				<div class="px-4 py-8 text-center text-sm text-gray-500">No transactions yet.</div>
			{:else}
				{#each transactions as txn (txn.id)}
					<div class="flex items-center justify-between px-4 py-3 transition hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
						<div>
							<p class="text-sm font-medium text-gray-900 dark:text-gray-200">
								{txn.type ?? txn.description ?? 'Transaction'}
							</p>
							<p class="text-xs text-gray-500">{formatDate(txn.created_at)}</p>
						</div>
						<div class="text-right">
							<p
								class="text-sm font-semibold {txn.direction === 'credit'
									? 'text-emerald-600 dark:text-green-400'
									: 'text-red-600 dark:text-red-400'}"
							>
								{txn.direction === 'credit' ? '+' : '-'}৳{formatMoney(txn.amount)}
							</p>
							<p class="text-xs text-gray-500">Bal: ৳{formatMoney(txn.balance_after)}</p>
						</div>
					</div>
				{/each}
			{/if}
		</div>
	</div>
</div>
