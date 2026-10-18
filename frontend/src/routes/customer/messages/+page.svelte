<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable from '$lib/components/DataTable.svelte';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';

	interface Message {
		id: number;
		to: string;
		sender_id: string;
		body: string;
		status: string;
		parts: number;
		cost: string;
		operator: string;
		submitted_at: string;
		delivered_at: string | null;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let messages = $state<Message[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');
	let statusFilter = $state('');
	let sortKey = $state('id');
	let sortDir = $state<'asc' | 'desc'>('desc');
	let dateFrom = $state('');
	let dateTo = $state('');

	async function load(page = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({
				page: String(page),
				per_page: String(meta.per_page),
				sort: sortKey,
				dir: sortDir
			});
			if (search) params.set('search', search);
			if (statusFilter) params.set('status', statusFilter);
			if (dateFrom) params.set('date_from', dateFrom);
			if (dateTo) params.set('date_to', dateTo);

			const data = await api.get<{ data: Message[]; meta: Meta }>(
				`/api/v1/customer/messages?${params}`
			);
			messages = data.data;
			meta = data.meta;
		} catch {
			messages = [];
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	function handleSort(key: string) {
		if (sortKey === key) {
			sortDir = sortDir === 'asc' ? 'desc' : 'asc';
		} else {
			sortKey = key;
			sortDir = 'asc';
		}
		load();
	}

	function handleSearch(val: string) {
		search = val;
		load(1);
	}

	function statusBadgeClass(row: Message): string {
		const map: Record<string, string> = {
			pending: 'badge-pending',
			sent: 'badge-sent',
			delivered: 'badge-delivered',
			failed: 'badge-failed',
			dlr_failed: 'badge-failed',
			processing: 'badge-processing'
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

	const columns = [
		{
			key: 'id',
			label: 'ID',
			class: 'w-20',
			render: (row: Message) => `MSG-${String(row.id).padStart(6, '0')}`
		},
		{ key: 'to', label: 'Recipient', class: 'font-mono' },
		{ key: 'sender_id', label: 'Sender ID' },
		{
			key: 'body',
			label: 'Message',
			class: 'max-w-[200px]',
			render: (row: Message) => (row.body?.length > 60 ? row.body.slice(0, 60) + '…' : row.body)
		},
		{ key: 'parts', label: 'Parts' },
		{ key: 'cost', label: 'Cost', money: true },
		{
			key: 'status',
			label: 'Status',
			badge: true,
			badgeClass: statusBadgeClass
		},
		{ key: 'operator', label: 'Operator' },
		{
			key: 'submitted_at',
			label: 'Submitted',
			render: (row: Message) => formatDate(row.submitted_at)
		},
		{
			key: 'delivered_at',
			label: 'Delivered',
			render: (row: Message) => formatDate(row.delivered_at)
		}
	];
</script>

<svelte:head>
	<title>SMS Logs – SMS Whiteinfo</title>
	<meta name="description" content="View all your sent SMS messages and delivery statuses." />
</svelte:head>

<div class="space-y-4">
	<div class="flex items-start justify-between gap-4">
		<div>
			<h2 class="text-lg font-semibold text-gray-950 dark:text-gray-100">SMS Logs</h2>
			<p class="mt-0.5 text-sm text-gray-500">All outgoing messages and delivery reports.</p>
		</div>
		<div class="flex gap-2">
			<a
				href="/customer/messages/quick"
				class="flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
				</svg>
				Quick Message
			</a>
			<a
				href="/customer/messages/bulk"
				class="flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500"
			>
				<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
				</svg>
				Bulk Message
			</a>
		</div>
	</div>

	<!-- Filters -->
	<div class="flex flex-wrap gap-3">
		<select
			bind:value={statusFilter}
			onchange={() => load(1)}
			class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
		>
			<option value="">All Statuses</option>
			<option value="pending">Pending</option>
			<option value="sent">Sent</option>
			<option value="delivered">Delivered</option>
			<option value="failed">Failed</option>
		</select>
		<input
			type="date"
			bind:value={dateFrom}
			onchange={() => load(1)}
			class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			placeholder="From date"
		/>
		<input
			type="date"
			bind:value={dateTo}
			onchange={() => load(1)}
			class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
			placeholder="To date"
		/>
	</div>

	<DataTable
		{columns}
		rows={messages}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSort={handleSort}
		{sortKey}
		{sortDir}
		bind:searchValue={search}
		onSearch={handleSearch}
		searchPlaceholder="Search by recipient, message, sender…"
		emptyMessage="No messages found matching your filters."
	/>
</div>
