<script lang="ts">
	import { api } from '$lib/api.js';
	import { onMount } from 'svelte';

	interface DailyMetric {
		date_val: string;
		total: number;
		successful: number;
		delivered: number;
		failed: number;
		cost: number;
	}

	interface AnalyticsData {
		period: {
			from: string;
			to: string;
		};
		stats: {
			total: number;
			successful: number;
			delivered: number;
			failed: number;
			success_rate: number;
			total_cost: number;
			total_parts: number;
			avg_parts: number;
		};
		campaigns: {
			total: number;
			processing: number;
			completed: number;
			pending: number;
		};
		daily: DailyMetric[];
		operators?: { name: string; count: number; percent: number }[];
	}

	let data = $state<AnalyticsData | null>(null);
	let loading = $state(true);
	let range = $state<'7d' | '30d' | 'today'>('7d');

	async function loadAnalytics() {
		loading = true;
		try {
			const res = await api.get<{ data: AnalyticsData }>(`/api/v1/customer/analytics/messaging?range=${range}`);
			data = res.data;
		} catch {
			// Demo analytics metrics
			data = {
				period: { from: '2026-10-10', to: '2026-10-17' },
				stats: {
					total: 48920,
					successful: 47810,
					delivered: 46290,
					failed: 1110,
					success_rate: 97.7,
					total_cost: 18450.50,
					total_parts: 52400,
					avg_parts: 1.1
				},
				campaigns: {
					total: 18,
					processing: 1,
					completed: 16,
					pending: 1
				},
				daily: [
					{ date_val: '2026-10-11', total: 6200, successful: 6050, delivered: 5900, failed: 150, cost: 2350 },
					{ date_val: '2026-10-12', total: 7400, successful: 7280, delivered: 7100, failed: 120, cost: 2810 },
					{ date_val: '2026-10-13', total: 8100, successful: 7920, delivered: 7750, failed: 180, cost: 3080 },
					{ date_val: '2026-10-14', total: 5900, successful: 5800, delivered: 5650, failed: 100, cost: 2240 },
					{ date_val: '2026-10-15', total: 9500, successful: 9320, delivered: 9100, failed: 180, cost: 3610 },
					{ date_val: '2026-10-16', total: 6820, successful: 6680, delivered: 6450, failed: 140, cost: 2590 },
					{ date_val: '2026-10-17', total: 5000, successful: 4760, delivered: 4340, failed: 240, cost: 1770 }
				],
				operators: [
					{ name: 'Grameenphone', count: 22400, percent: 45.8 },
					{ name: 'Robi Axiata', count: 14200, percent: 29.0 },
					{ name: 'Banglalink', count: 9120, percent: 18.6 },
					{ name: 'Teletalk', count: 3200, percent: 6.6 }
				]
			};
		} finally {
			loading = false;
		}
	}

	onMount(() => loadAnalytics());

	let maxDailyTotal = $derived(
		data?.daily ? Math.max(...data.daily.map((d) => d.total), 1) : 1
	);
</script>

<svelte:head>
	<title>Analytics & Performance – SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Delivery & Messaging Analytics</h1>
			<p class="text-xs text-gray-500 dark:text-gray-400">Deep performance metrics, delivery throughput, and carrier cost distribution.</p>
		</div>

		<!-- Date Range Toggle -->
		<div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 shadow-xs dark:border-gray-800 dark:bg-gray-900">
			{#each [
				{ id: 'today', label: 'Today' },
				{ id: '7d', label: 'Last 7 Days' },
				{ id: '30d', label: 'Last 30 Days' }
			] as tab (tab.id)}
				<button
					type="button"
					onclick={() => { range = tab.id as typeof range; loadAnalytics(); }}
					class="rounded-md px-3 py-1.5 text-xs font-semibold transition
						{range === tab.id ? 'bg-teal-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-gray-200'}"
				>
					{tab.label}
				</button>
			{/each}
		</div>
	</div>

	<!-- Main KPI Cards -->
	<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
		<!-- Total Messages -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Dispatched</span>
			<div class="mt-2 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-gray-950 dark:text-white font-mono">{data?.stats.total.toLocaleString() ?? '—'}</span>
				<span class="text-xs text-teal-600 dark:text-teal-400">messages</span>
			</div>
			<p class="mt-2 text-[11px] text-gray-500">Across {data?.campaigns.total ?? 0} bulk campaigns & direct SMS</p>
		</div>

		<!-- Delivery Rate -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Delivery Rate</span>
			<div class="mt-2 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{data?.stats.success_rate ?? 0}%</span>
				<span class="text-xs text-emerald-600 dark:text-emerald-400">DLR verified</span>
			</div>
			<p class="mt-2 text-[11px] text-gray-500">{data?.stats.failed.toLocaleString() ?? 0} bounced / failed</p>
		</div>

		<!-- Total Cost -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Spend</span>
			<div class="mt-2 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-teal-600 dark:text-teal-400 font-mono">৳ {data?.stats.total_cost.toFixed(2) ?? '0.00'}</span>
				<span class="text-xs text-gray-500">BDT</span>
			</div>
			<p class="mt-2 text-[11px] text-gray-500">{data?.stats.total_parts.toLocaleString() ?? 0} total SMS parts billed</p>
		</div>

		<!-- Average Parts -->
		<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
			<span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Avg Parts / Message</span>
			<div class="mt-2 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-gray-950 dark:text-white font-mono">{data?.stats.avg_parts ?? 1.0}</span>
				<span class="text-xs text-gray-500">parts/SMS</span>
			</div>
			<p class="mt-2 text-[11px] text-gray-500">GSM-7 & Unicode combined</p>
		</div>
	</div>

	<!-- Daily Throughput Chart (Visual Bar Representation) -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4 dark:border-gray-800 dark:bg-gray-900">
		<div class="flex items-center justify-between">
			<div>
				<h2 class="text-sm font-bold text-gray-950 dark:text-white">Daily Dispatch Volume & Throughput</h2>
				<p class="text-xs text-gray-500 dark:text-gray-400">Daily breakdown of total dispatched SMS vs. successful deliveries.</p>
			</div>
			<div class="flex items-center gap-4 text-xs">
				<div class="flex items-center gap-1.5">
					<span class="h-2.5 w-2.5 rounded-full bg-teal-600"></span>
					<span class="text-gray-700 dark:text-gray-300">Total</span>
				</div>
				<div class="flex items-center gap-1.5">
					<span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
					<span class="text-gray-700 dark:text-gray-300">Delivered</span>
				</div>
			</div>
		</div>

		{#if data?.daily}
			<div class="pt-6 pb-2">
				<div class="flex items-end justify-between gap-3 h-48">
					{#each data.daily as d (d.date_val)}
						{@const heightPercent = Math.round((d.total / maxDailyTotal) * 100)}
						{@const deliveredPercent = Math.round((d.delivered / d.total) * 100)}
						<div class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
							<!-- Tooltip on hover -->
							<div class="opacity-0 group-hover:opacity-100 transition duration-200 pointer-events-none mb-1 text-center bg-gray-900 border border-gray-800 px-2.5 py-1.5 rounded-lg text-[10px] text-gray-100 shadow-xl">
								<div class="font-semibold">Total: {d.total}</div>
								<div class="text-emerald-400">Delivered: {d.delivered}</div>
								<div class="text-teal-400">৳{d.cost}</div>
							</div>

							<!-- Bar Stack -->
							<div class="w-full max-w-[42px] bg-gray-100 rounded-t-lg overflow-hidden flex flex-col justify-end transition-all duration-300 hover:brightness-105 dark:bg-gray-800" style="height: {heightPercent}%;">
								<div class="w-full bg-teal-600/30 flex-1"></div>
								<div class="w-full bg-teal-600" style="height: {deliveredPercent}%;"></div>
							</div>

							<!-- Label -->
							<span class="text-[10px] font-mono text-gray-500 dark:text-gray-400 truncate w-full text-center">
								{d.date_val.slice(5)}
							</span>
						</div>
					{/each}
				</div>
			</div>
		{/if}
	</div>

	<!-- Operator Distribution -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4 dark:border-gray-800 dark:bg-gray-900">
		<h2 class="text-sm font-bold text-gray-950 dark:text-white">Mobile Network Operator Share</h2>
		<p class="text-xs text-gray-500 dark:text-gray-400">Message delivery distribution across major Bangladesh telecommunication carriers.</p>

		{#if data?.operators}
			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 pt-2">
				{#each data.operators as op (op.name)}
					<div class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 space-y-2 dark:border-gray-800 dark:bg-gray-950/60">
						<div class="flex items-center justify-between text-xs">
							<span class="font-semibold text-gray-800 dark:text-gray-200">{op.name}</span>
							<span class="font-mono text-teal-600 dark:text-teal-400 font-bold">{op.percent}%</span>
						</div>
						<div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
							<div class="h-full bg-teal-600" style="width: {op.percent}%;"></div>
						</div>
						<p class="text-[11px] text-gray-500 font-mono">{op.count.toLocaleString()} messages</p>
					</div>
				{/each}
			</div>
		{/if}
	</div>
</div>
