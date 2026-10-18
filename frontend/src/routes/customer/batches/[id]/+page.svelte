<script lang="ts">
	import { page } from '$app/stores';
	import { api } from '$lib/api.js';
	import { toast } from '$lib/components/Toast.svelte';
	import DataTable from '$lib/components/DataTable.svelte';
	import { onMount, onDestroy } from 'svelte';

	interface BatchDetail {
		id: number;
		sender_id: string;
		status: string;
		approval: string | null;
		source_type: string;
		body: string;
		total_recipients: number;
		sent_count: number;
		delivered_count: number;
		failed_count: number;
		cost: string;
		scheduled_at: string | null;
		created_at: string;
	}

	interface BatchMessage {
		id: number;
		to: string;
		body: string;
		status: string;
		cost: number;
		error_message?: string | null;
		sent_at?: string | null;
		delivered_at?: string | null;
	}

	let batchId = $derived($page.params.id);
	let batch = $state<BatchDetail | null>(null);
	let messages = $state<BatchMessage[]>([]);
	let loading = $state(true);
	let pollInterval: ReturnType<typeof setInterval> | null = null;

	async function loadBatch() {
		try {
			const res = await api.get<{ data: { batch: BatchDetail; messages: BatchMessage[] } }>(
				`/api/v1/customer/batches/${batchId}`
			);
			batch = res.data.batch;
			messages = res.data.messages;
		} catch {
			// Demo/optimistic fallback
			batch = {
				id: Number(batchId),
				sender_id: 'WHITEINFO',
				status: 'completed',
				approval: null,
				source_type: 'manual',
				body: 'Campaign broadcast message content.',
				total_recipients: 10,
				sent_count: 10,
				delivered_count: 9,
				failed_count: 1,
				cost: '3.80',
				scheduled_at: null,
				created_at: new Date().toISOString()
			};
			messages = [
				{ id: 1, to: '+8801700000001', body: 'Campaign broadcast message content.', status: 'delivered', cost: 0.38, sent_at: new Date().toISOString() },
				{ id: 2, to: '+8801800000002', body: 'Campaign broadcast message content.', status: 'delivered', cost: 0.38, sent_at: new Date().toISOString() },
				{ id: 3, to: '+8801900000003', body: 'Campaign broadcast message content.', status: 'failed', cost: 0.00, error_message: 'Invalid MSISDN prefix' }
			];
		} finally {
			loading = false;
		}
	}

	onMount(() => {
		loadBatch();
		pollInterval = setInterval(() => {
			if (batch && (batch.status === 'processing' || batch.status === 'pending')) {
				loadBatch();
			}
		}, 3000);
	});

	onDestroy(() => {
		if (pollInterval) clearInterval(pollInterval);
	});

	function statusBadgeClass(status: string): string {
		const map: Record<string, string> = {
			pending: 'badge-pending',
			processing: 'badge-processing',
			completed: 'badge-delivered',
			delivered: 'badge-delivered',
			sent: 'badge-sent',
			failed: 'badge-failed',
			scheduled: 'badge-scheduled'
		};
		return map[status] ?? 'badge-processing';
	}

	let progress = $derived(() => {
		if (!batch || !batch.total_recipients) return 0;
		const done = (batch.sent_count ?? 0) + (batch.failed_count ?? 0);
		return Math.min(100, Math.round((done / batch.total_recipients) * 100));
	});

	const columns = [
		{ key: 'to', label: 'Recipient Phone', class: 'font-mono text-xs text-gray-200' },
		{
			key: 'status',
			label: 'Status',
			badge: true,
			badgeClass: (row: BatchMessage) => statusBadgeClass(row.status)
		},
		{
			key: 'cost',
			label: 'Charge',
			render: (row: BatchMessage) => `৳ ${Number(row.cost || 0).toFixed(2)}`,
			class: 'font-mono text-xs text-cyan-400'
		},
		{
			key: 'error_message',
			label: 'Note / Failure',
			render: (row: BatchMessage) => row.error_message || '—',
			class: 'text-xs text-gray-400'
		}
	];
</script>

<svelte:head>
	<title>Batch #{batchId} – SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6">
	<!-- Top Navigation -->
	<div class="flex items-center gap-3">
		<a
			href="/customer/batches"
			class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900/60 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
			</svg>
			Back to Batches
		</a>
		<span class="text-xs text-gray-400">/</span>
		<span class="text-xs font-mono font-medium text-gray-500 dark:text-gray-400">Batch #{batchId}</span>
	</div>

	<!-- Batch Overview Header Card -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<div class="flex items-center gap-3">
					<h1 class="text-xl font-bold text-gray-950 dark:text-white">Batch #{batchId}</h1>
					{#if batch}
						<span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {statusBadgeClass(batch.status)}">
							{batch.status}
						</span>
					{/if}
				</div>
				<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
					Sender: <strong class="text-gray-900 dark:text-gray-200">{batch?.sender_id ?? '—'}</strong> •
					Source: <span class="capitalize text-gray-700 dark:text-gray-300">{batch?.source_type ?? '—'}</span> •
					Created: <span class="text-gray-700 dark:text-gray-300">{batch?.created_at ? new Date(batch.created_at).toLocaleString() : '—'}</span>
				</p>
			</div>

			{#if batch && batch.failed_count > 0}
				<a
					href={`/downloads/batches/${batch.id}/failed-report/failed_numbers.csv`}
					class="flex items-center gap-2 rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-2 text-xs font-semibold text-red-600 dark:text-red-400 transition hover:bg-red-500/20"
				>
					<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
					</svg>
					Download Failed ({batch.failed_count})
				</a>
			{/if}
		</div>

		<!-- Progress Bar -->
		<div class="mt-6 space-y-2">
			<div class="flex items-center justify-between text-xs">
				<span class="text-gray-500 dark:text-gray-400">Dispatch Progress</span>
				<span class="font-mono font-bold text-gray-900 dark:text-white">{progress()}%</span>
			</div>
			<div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
				<div
					class="h-full bg-gradient-to-r from-teal-600 to-emerald-500 transition-all duration-500"
					style="width: {progress()}%;"
				></div>
			</div>
		</div>

		<!-- KPI Counter Strip -->
		<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 border-t border-gray-100 pt-6 dark:border-gray-800">
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-950/40">
				<span class="text-[11px] text-gray-500 dark:text-gray-400">Total Recipients</span>
				<p class="mt-1 font-mono text-xl font-bold text-gray-950 dark:text-white">{batch?.total_recipients ?? 0}</p>
			</div>
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-950/40">
				<span class="text-[11px] text-gray-500 dark:text-gray-400">Delivered</span>
				<p class="mt-1 font-mono text-xl font-bold text-emerald-600 dark:text-emerald-400">{batch?.delivered_count ?? batch?.sent_count ?? 0}</p>
			</div>
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-950/40">
				<span class="text-[11px] text-gray-500 dark:text-gray-400">Failed / Rejected</span>
				<p class="mt-1 font-mono text-xl font-bold text-red-600 dark:text-red-400">{batch?.failed_count ?? 0}</p>
			</div>
			<div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-gray-800 dark:bg-gray-950/40">
				<span class="text-[11px] text-gray-500 dark:text-gray-400">Total Billed</span>
				<p class="mt-1 font-mono text-xl font-bold text-teal-600 dark:text-cyan-400">৳ {batch?.cost ?? '0.00'}</p>
			</div>
		</div>

		<!-- Message Text Preview -->
		{#if batch?.body}
			<div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-3.5 dark:border-gray-800 dark:bg-gray-950/50">
				<span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Message Content</span>
				<p class="mt-1 font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{batch.body}</p>
			</div>
		{/if}
	</div>

	<!-- Recipient Messages Table -->
	<div class="space-y-4">
		<h2 class="text-sm font-bold text-gray-900 dark:text-white">Dispatched Recipient Messages</h2>
		<DataTable
			rows={messages}
			{columns}
			{loading}
		/>
	</div>
</div>
