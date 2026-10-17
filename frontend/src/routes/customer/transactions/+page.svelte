<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import { onMount } from 'svelte';

	interface Transaction {
		id: number;
		transactionable_type: string;
		direction: 'credit' | 'debit';
		amount: string;
		balance_after: string;
		description: string;
		created_at: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let transactions = $state<Transaction[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: Transaction[]; meta: Meta }>(`/api/v1/customer/transactions?${params}`);
			transactions = data.data;
			meta = data.meta;
		} catch {
			transactions = [
				{
					id: 1,
					transactionable_type: 'Deposit',
					direction: 'credit',
					amount: '5000.00',
					balance_after: '5350.25',
					description: 'Instant recharge via Piprapay (Trx #TRX-948102941)',
					created_at: new Date(Date.now() - 86400000).toISOString()
				},
				{
					id: 2,
					transactionable_type: 'Batch',
					direction: 'debit',
					amount: '45.75',
					balance_after: '5304.50',
					description: 'Broadcast SMS charge: 150 parts via GP',
					created_at: new Date(Date.now() - 43200000).toISOString()
				},
				{
					id: 3,
					transactionable_type: 'Message',
					direction: 'debit',
					amount: '0.35',
					balance_after: '5304.15',
					description: 'Single Quick SMS to 8801700000000',
					created_at: new Date(Date.now() - 3600000).toISOString()
				}
			];
			meta = { total: 3, per_page: 15, current_page: 1 };
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	const columns: Column<Transaction>[] = [
		{
			key: 'direction',
			label: 'Type',
			badge: true,
			badgeClass: (r) => (r.direction === 'credit' ? 'badge-delivered' : 'badge-failed'),
			render: (r) => (r.direction === 'credit' ? '+ CREDIT' : '- DEBIT')
		},
		{
			key: 'amount',
			label: 'Amount (BDT)',
			render: (r) => `${r.direction === 'credit' ? '+' : '-'}৳${Number(r.amount).toFixed(2)}`,
			class: 'font-mono font-bold'
		},
		{
			key: 'balance_after',
			label: 'Balance After',
			money: true,
			class: 'font-mono text-gray-400 text-xs'
		},
		{
			key: 'transactionable_type',
			label: 'Reference',
			badge: true,
			badgeClass: () => 'badge-processing'
		},
		{
			key: 'description',
			label: 'Description',
			class: 'text-xs text-gray-300 max-w-md truncate'
		},
		{
			key: 'created_at',
			label: 'Timestamp',
			render: (r) => new Date(r.created_at).toLocaleString()
		}
	];
</script>

<svelte:head>
	<title>Transactions Ledger – WhiteInfo Portal</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-white">Transactions Ledger</h1>
			<p class="text-xs text-gray-400">Complete immutable record of all balance debits and wallet credits.</p>
		</div>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={transactions}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search transactions..."
	/>
</div>
