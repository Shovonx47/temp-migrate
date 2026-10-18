<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable from '$lib/components/DataTable.svelte';
	import { onMount } from 'svelte';
	import { page } from '$app/stores';

	interface Batch {
		id: number;
		status: string;
		approval: string | null;
		source_type: string;
		total_recipients: number;
		sent_count: number;
		failed_count: number;
		cost: string;
		scheduled_at: string | null;
		created_at: string;
		sender_id: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let batches = $state<Batch[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: Batch[]; meta: Meta }>(`/api/v1/customer/batches?${params}`);
			batches = data.data;
			meta = data.meta;
		} catch {
			batches = [];
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	function statusBadgeClass(row: Batch): string {
		const map: Record<string, string> = {
			pending: 'badge-pending',
			processing: 'badge-processing',
			completed: 'badge-delivered',
			failed: 'badge-failed',
			scheduled: 'badge-scheduled',
			cancelled: 'badge-failed'
		};
		return map[row.status] ?? 'badge-processing';
	}

	function formatDate(iso: string | null): string {
		if (!iso) return '—';
		return new Date(iso).toLocaleString('en-GB', {
			day: '2-digit',
			month: 'short',
			hour: '2-digit',
			minute: '2-digit'
		});
	}

	function progressWidth(batch: Batch): string {
		if (!batch.total_recipients || batch.total_recipients === 0) return '0%';
		const done = (batch.sent_count ?? 0) + (batch.failed_count ?? 0);
		return `${Math.min(100, Math.round((done / batch.total_recipients) * 100))}%`;
	}

	const columns = [
		{
			key: 'id',
			label: 'Batch ID',
			class: 'w-24 font-mono',
			render: (row: Batch) => `<a href="/customer/batches/${row.id}" class="text-primary-400 hover:text-primary-300 hover:underline font-semibold">#${row.id}</a>`
		},
		{ key: 'sender_id', label: 'Sender ID' },
		{ key: 'source_type', label: 'Source', badge: true, badgeClass: () => 'badge-processing' },
		{ key: 'total_recipients', label: 'Recipients' },
		{
			key: 'progress',
			label: 'Progress',
			render: (row: Batch) => {
				const total = row.total_recipients || 0;
				const done = (row.sent_count ?? 0) + (row.failed_count ?? 0);
				return total > 0 ? `${done}/${total}` : '—';
			}
		},
		{ key: 'cost', label: 'Cost', money: true },
		{ key: 'status', label: 'Status', badge: true, badgeClass: statusBadgeClass },
		{
			key: 'scheduled_at',
			label: 'Scheduled',
			render: (row: Batch) => formatDate(row.scheduled_at)
		},
		{
			key: 'created_at',
			label: 'Created',
			render: (row: Batch) => formatDate(row.created_at)
		}
	];
</script>

<svelte:head>
	<title>Batches – SMS Whiteinfo</title>
	<meta name="description" content="View all your SMS batch campaigns and their progress." />
</svelte:head>

<div class="space-y-4">
	<div class="flex items-start justify-between gap-4">
		<div>
			<h2 class="text-lg font-semibold text-gray-950 dark:text-gray-100">Batches</h2>
			<p class="mt-0.5 text-sm text-gray-500">All bulk campaigns and their delivery progress.</p>
		</div>
		<a
			href="/customer/messages/bulk"
			class="flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			New Batch
		</a>
	</div>

	<DataTable
		{columns}
		rows={batches}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		bind:searchValue={search}
		onSearch={(v) => { search = v; load(1); }}
		searchPlaceholder="Search batches…"
		emptyMessage="No batches found."
	/>
</div>
