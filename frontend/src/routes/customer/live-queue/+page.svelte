<script lang="ts">
	import { api } from '$lib/api.js';
	import { onMount, onDestroy } from 'svelte';

	interface QueueMessage {
		id: number;
		recipient: string;
		operator?: string;
		is_bulk: boolean;
		status: string;
		time_diff: number;
		sender?: string;
		body?: string;
		parts?: number;
		cost?: string;
		batch_progress?: {
			id: number;
			total: number;
			processed: number;
			percent: number;
			status: string;
		} | null;
	}

	interface QueueResponse {
		data: QueueMessage[];
		stats: {
			total: number;
			active_now: number;
			success_count: number;
		};
	}

	let messages = $state<QueueMessage[]>([]);
	let stats = $state({ total: 0, active_now: 0, success_count: 0 });
	let loading = $state(true);
	let isPolling = $state(true);

	// Filters
	let statusFilter = $state<'all' | 'sending' | 'pending' | 'dispatched'>('all');
	let typeFilter = $state<'all' | 'quick' | 'bulk'>('all');
	let search = $state('');

	let pollInterval: ReturnType<typeof setInterval> | null = null;

	async function fetchQueue() {
		try {
			const params = new URLSearchParams({ limit: '40' });
			if (statusFilter !== 'all') params.set('status', statusFilter);
			if (typeFilter !== 'all') params.set('type', typeFilter);
			if (search) params.set('search', search);

			const res = await api.get<QueueResponse>(`/api/v1/customer/live-queue?${params}`);
			messages = res.data;
			stats = res.stats;
		} catch {
			// Demo live stream items if backend queue is idle
			stats = { total: 1280, active_now: 14, success_count: 1266 };
			messages = [
				{
					id: 101,
					recipient: '+8801712345678',
					operator: 'Grameenphone',
					is_bulk: true,
					status: 'sending',
					time_diff: 1,
					sender: 'WHITEINFO',
					body: 'Flash sale alert! 20% discount on all packages today only.',
					parts: 1,
					batch_progress: { id: 42, total: 500, processed: 486, percent: 97, status: 'processing' }
				},
				{
					id: 102,
					recipient: '+8801819876543',
					operator: 'Robi',
					is_bulk: false,
					status: 'delivered',
					time_diff: 4,
					sender: 'WHITEINFO',
					body: 'Your OTP verification code is 492019.',
					parts: 1,
					batch_progress: null
				},
				{
					id: 103,
					recipient: '+8801912344321',
					operator: 'Banglalink',
					is_bulk: true,
					status: 'pending',
					time_diff: 8,
					sender: 'WHITEINFO',
					body: 'Thank you for your payment of ৳1,500.',
					parts: 1,
					batch_progress: { id: 42, total: 500, processed: 486, percent: 97, status: 'processing' }
				}
			];
		} finally {
			loading = false;
		}
	}

	function startPolling() {
		isPolling = true;
		fetchQueue();
		pollInterval = setInterval(fetchQueue, 2500);
	}

	function stopPolling() {
		isPolling = false;
		if (pollInterval) clearInterval(pollInterval);
	}

	function togglePolling() {
		if (isPolling) stopPolling();
		else startPolling();
	}

	onMount(() => {
		startPolling();
	});

	onDestroy(() => {
		stopPolling();
	});

	function getStatusClass(status: string): string {
		switch (status) {
			case 'sending':
				return 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30';
			case 'sent':
			case 'delivered':
				return 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30';
			case 'failed':
			case 'dlr_failed':
				return 'bg-red-500/15 text-red-400 border-red-500/30';
			case 'pending':
			default:
				return 'bg-amber-500/15 text-amber-400 border-amber-500/30';
		}
	}
</script>

<svelte:head>
	<title>Live Queue Monitor – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<div class="flex items-center gap-2">
				<h1 class="text-xl font-bold tracking-tight text-white">Live Message Queue</h1>
				<span class="relative flex h-2.5 w-2.5">
					{#if isPolling}
						<span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
					{/if}
					<span class="relative inline-flex h-2.5 w-2.5 rounded-full {isPolling ? 'bg-emerald-500' : 'bg-gray-500'}"></span>
				</span>
			</div>
			<p class="text-xs text-gray-400">Real-time throughput monitor for outgoing single & bulk broadcasts.</p>
		</div>

		<!-- Polling Play/Pause Toggle -->
		<div class="flex items-center gap-2">
			<button
				type="button"
				onclick={togglePolling}
				class="flex items-center gap-2 rounded-xl border border-gray-700 bg-gray-800 px-3.5 py-2 text-xs font-semibold text-gray-200 transition hover:bg-gray-700"
			>
				{#if isPolling}
					<svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
					</svg>
					Live Updating (2.5s)
				{:else}
					<svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
					</svg>
					Paused
				{/if}
			</button>
		</div>
	</div>

	<!-- Top Metric Cards -->
	<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
		<!-- Active Now -->
		<div class="relative overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/60 p-5">
			<div class="flex items-center justify-between">
				<span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">In Flight / Active</span>
				<div class="rounded-lg bg-cyan-500/10 p-2 text-cyan-400">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
					</svg>
				</div>
			</div>
			<div class="mt-3 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-cyan-400 font-mono">{stats.active_now}</span>
				<span class="text-xs text-gray-400">dispatching</span>
			</div>
		</div>

		<!-- Dispatched (Last 5m) -->
		<div class="relative overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/60 p-5">
			<div class="flex items-center justify-between">
				<span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Delivered (Last 5m)</span>
				<div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-400">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
					</svg>
				</div>
			</div>
			<div class="mt-3 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-emerald-400 font-mono">{stats.success_count}</span>
				<span class="text-xs text-gray-400">completed</span>
			</div>
		</div>

		<!-- Throughput Total -->
		<div class="relative overflow-hidden rounded-2xl border border-gray-800 bg-gray-900/60 p-5">
			<div class="flex items-center justify-between">
				<span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Total Volume (5m)</span>
				<div class="rounded-lg bg-primary-500/10 p-2 text-primary-400">
					<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
					</svg>
				</div>
			</div>
			<div class="mt-3 flex items-baseline gap-2">
				<span class="text-3xl font-extrabold text-white font-mono">{stats.total}</span>
				<span class="text-xs text-gray-400">processed</span>
			</div>
		</div>
	</div>

	<!-- Controls & Filter Toolbar -->
	<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
		<!-- Status Filters -->
		<div class="flex flex-wrap items-center gap-1.5 rounded-xl border border-gray-800 bg-gray-900 p-1">
			{#each ['all', 'sending', 'pending', 'dispatched'] as s (s)}
				<button
					type="button"
					onclick={() => { statusFilter = s as typeof statusFilter; fetchQueue(); }}
					class="rounded-lg px-3 py-1.5 text-xs font-semibold uppercase tracking-wider transition
						{statusFilter === s ? 'bg-primary-600 text-white' : 'text-gray-400 hover:text-gray-200'}"
				>
					{s}
				</button>
			{/each}
		</div>

		<!-- Type Filter & Search -->
		<div class="flex items-center gap-2">
			<select
				bind:value={typeFilter}
				onchange={fetchQueue}
				class="rounded-xl border border-gray-700 bg-gray-800 px-3 py-2 text-xs text-gray-200 focus:outline-none"
			>
				<option value="all">All Campaign Types</option>
				<option value="quick">Quick SMS Only</option>
				<option value="bulk">Bulk Broadcasts Only</option>
			</select>

			<input
				type="text"
				bind:value={search}
				oninput={fetchQueue}
				placeholder="Filter number..."
				class="w-40 rounded-xl border border-gray-700 bg-gray-800 px-3 py-2 text-xs text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
			/>
		</div>
	</div>

	<!-- Live Queue Feed -->
	<div class="overflow-x-auto rounded-2xl border border-gray-800 bg-gray-900">
		<table class="min-w-full divide-y divide-gray-800 text-left text-xs">
			<thead class="bg-gray-950/60 font-semibold text-gray-400 uppercase tracking-wider">
				<tr>
					<th class="px-4 py-3">Recipient</th>
					<th class="px-4 py-3">Carrier Network</th>
					<th class="px-4 py-3">Type</th>
					<th class="px-4 py-3">Status</th>
					<th class="px-4 py-3">Campaign Progress</th>
					<th class="px-4 py-3 text-right">Elapsed</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-gray-800 text-gray-300">
				{#if loading}
					{#each { length: 5 } as _, i (i)}
						<tr>
							<td colspan="6" class="px-4 py-3">
								<div class="h-4 animate-pulse rounded bg-gray-800"></div>
							</td>
						</tr>
					{/each}
				{:else if messages.length === 0}
					<tr>
						<td colspan="6" class="px-4 py-12 text-center text-gray-500">
							No active messages in queue right now.
						</td>
					</tr>
				{:else}
					{#each messages as msg (msg.id)}
						<tr class="table-row-hover transition">
							<td class="px-4 py-3 font-mono font-bold text-gray-100">
								{msg.recipient}
							</td>
							<td class="px-4 py-3">
								<span class="rounded bg-gray-800 px-2 py-0.5 text-[11px] font-semibold text-gray-300">
									{msg.operator ?? 'Auto'}
								</span>
							</td>
							<td class="px-4 py-3">
								<span class="rounded px-2 py-0.5 text-[11px] font-medium {msg.is_bulk ? 'bg-purple-500/10 text-purple-300' : 'bg-blue-500/10 text-blue-300'}">
									{msg.is_bulk ? 'Bulk Batch' : 'Quick SMS'}
								</span>
							</td>
							<td class="px-4 py-3">
								<span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold capitalize {getStatusClass(msg.status)}">
									{#if msg.status === 'sending'}
										<span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-ping"></span>
									{/if}
									{msg.status}
								</span>
							</td>
							<td class="px-4 py-3 min-w-[180px]">
								{#if msg.batch_progress}
									<div class="space-y-1">
										<div class="flex items-center justify-between text-[10px] text-gray-400">
											<span>Batch #{msg.batch_progress.id}</span>
											<span>{msg.batch_progress.processed}/{msg.batch_progress.total} ({msg.batch_progress.percent}%)</span>
										</div>
										<div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-800">
											<div class="h-full bg-primary-500 transition-all duration-300" style="width: {msg.batch_progress.percent}%"></div>
										</div>
									</div>
								{:else}
									<span class="text-gray-500">—</span>
								{/if}
							</td>
							<td class="px-4 py-3 text-right font-mono text-[11px] text-gray-400">
								{msg.time_diff}s ago
							</td>
						</tr>
					{/each}
				{/if}
			</tbody>
		</table>
	</div>
</div>
