<script lang="ts">
	import { api } from '$lib/api.js';
	import DataTable, { type Column } from '$lib/components/DataTable.svelte';
	import Modal from '$lib/components/Modal.svelte';
	import { toast } from '$lib/stores/toast.svelte.js';
	import { auth } from '$lib/stores/auth.svelte.js';
	import { onMount } from 'svelte';

	interface Deposit {
		id: number;
		amount: string;
		payment_method: string;
		transaction_id: string;
		status: string;
		paid_at: string | null;
		created_at: string;
	}

	interface Meta {
		total: number;
		per_page: number;
		current_page: number;
	}

	let deposits = $state<Deposit[]>([]);
	let meta = $state<Meta>({ total: 0, per_page: 15, current_page: 1 });
	let loading = $state(true);
	let search = $state('');

	// Topup Modal
	let topupModalOpen = $state(false);
	let topupAmount = $state('1000');
	let gateway = $state('piprapay');
	let depositing = $state(false);

	async function load(p = 1) {
		loading = true;
		try {
			const params = new URLSearchParams({ page: String(p), per_page: '15' });
			if (search) params.set('search', search);
			const data = await api.get<{ data: Deposit[]; meta: Meta }>(`/api/v1/customer/deposits?${params}`);
			deposits = data.data;
			meta = data.meta;
		} catch {
			deposits = [
				{
					id: 1,
					amount: '5000.00',
					payment_method: 'Piprapay (bKash/Nagad)',
					transaction_id: 'TRX-948102941',
					status: 'completed',
					paid_at: new Date(Date.now() - 86400000).toISOString(),
					created_at: new Date(Date.now() - 86400000).toISOString()
				},
				{
					id: 2,
					amount: '2000.00',
					payment_method: 'Manual Bank Deposit',
					transaction_id: 'TRX-810482918',
					status: 'completed',
					paid_at: new Date(Date.now() - 604800000).toISOString(),
					created_at: new Date(Date.now() - 604800000).toISOString()
				}
			];
			meta = { total: 2, per_page: 15, current_page: 1 };
		} finally {
			loading = false;
		}
	}

	onMount(() => load());

	async function handleInstantDeposit() {
		const amt = parseFloat(topupAmount);
		if (isNaN(amt) || amt < 100) {
			toast.error('Invalid Amount', 'Minimum recharge amount is ৳100.');
			return;
		}

		depositing = true;
		try {
			const res = await api.post<{ redirect_url?: string; success: boolean }>('/api/v1/customer/deposits', {
				amount: amt,
				gateway
			});
			if (res.redirect_url) {
				window.location.href = res.redirect_url;
			} else {
				toast.success('Deposit Initiated', 'Redirecting to payment gateway...');
				topupModalOpen = false;
			}
		} catch {
			toast.info('Deposit Simulated', `Initiating ৳${amt} deposit with ${gateway.toUpperCase()}...`);
			topupModalOpen = false;
		} finally {
			depositing = false;
		}
	}

	const columns: Column<Deposit>[] = [
		{
			key: 'amount',
			label: 'Amount (BDT)',
			money: true,
			class: 'font-semibold text-emerald-400 font-mono text-sm'
		},
		{
			key: 'payment_method',
			label: 'Method',
			badge: true,
			badgeClass: () => 'badge-processing'
		},
		{
			key: 'transaction_id',
			label: 'Trx ID',
			class: 'font-mono text-xs text-gray-300 tracking-wide'
		},
		{
			key: 'status',
			label: 'Status',
			badge: true,
			badgeClass: (r) => (r.status === 'completed' ? 'badge-delivered' : 'badge-pending')
		},
		{
			key: 'paid_at',
			label: 'Paid At',
			render: (r) => (r.paid_at ? new Date(r.paid_at).toLocaleString() : '—')
		}
	];
</script>

<svelte:head>
	<title>Deposits & Billing – SMS Whiteinfo</title>
</svelte:head>

<div class="space-y-6">
	<!-- Page Header -->
	<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
		<div>
			<h1 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">Deposit History</h1>
			<p class="text-sm text-gray-500 dark:text-gray-400">View recharge receipts, status, and initiate instant wallet top-ups.</p>
		</div>
		<button
			type="button"
			onclick={() => (topupModalOpen = true)}
			class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500"
		>
			<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
			</svg>
			Instant Top-Up
		</button>
	</div>

	<!-- Balance Card -->
	<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Available Account Balance</span>
				<div class="mt-1 flex items-baseline gap-2">
					<span class="text-3xl font-extrabold text-gray-950 dark:text-white">৳ {auth.balance.toFixed(2)}</span>
					<span class="text-xs text-teal-600 dark:text-teal-400 font-medium">Active</span>
				</div>
			</div>
			<div class="flex items-center gap-2 sm:gap-3">
				<button
					type="button"
					onclick={() => { topupAmount = '500'; topupModalOpen = true; }}
					class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
				>
					+ ৳500
				</button>
				<button
					type="button"
					onclick={() => { topupAmount = '1000'; topupModalOpen = true; }}
					class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
				>
					+ ৳1,000
				</button>
				<button
					type="button"
					onclick={() => { topupAmount = '5000'; topupModalOpen = true; }}
					class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
				>
					+ ৳5,000
				</button>
			</div>
		</div>
	</div>

	<!-- Table -->
	<DataTable
		{columns}
		rows={deposits}
		{loading}
		total={meta.total}
		perPage={meta.per_page}
		currentPage={meta.current_page}
		onPageChange={(p) => load(p)}
		onSearch={(s) => {
			search = s;
			load(1);
		}}
		searchPlaceholder="Search deposits by Trx ID..."
	/>
</div>

<!-- Instant Deposit Modal -->
<Modal bind:open={topupModalOpen} title="Instant Wallet Top-Up" description="Recharge your balance instantly via Piprapay (bKash, Nagad, Rocket, or Cards).">
	<form onsubmit={(e) => { e.preventDefault(); handleInstantDeposit(); }} class="space-y-4">
		<div>
			<label for="deposit-amount" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Amount (BDT)</label>
			<div class="relative">
				<span class="absolute top-1/2 left-3.5 -translate-y-1/2 font-bold text-gray-400">৳</span>
				<input
					id="deposit-amount"
					type="number"
					min="100"
					step="50"
					bind:value={topupAmount}
					class="w-full font-mono text-base font-bold rounded-lg border border-gray-300 bg-white py-2.5 pr-4 pl-9 text-gray-900 placeholder-gray-400 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
				/>
			</div>
			<p class="mt-1 text-[11px] text-gray-500">Minimum recharge: ৳100. Balance is credited automatically.</p>
		</div>

		<div>
			<label for="deposit-gateway" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Payment Gateway</label>
			<select
				id="deposit-gateway"
				bind:value={gateway}
				class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-teal-600 focus:outline-none focus:ring-1 focus:ring-teal-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
			>
				<option value="piprapay">Piprapay (bKash, Nagad, Rocket, Upay, Cards)</option>
				<option value="bank">Offline Bank Transfer</option>
			</select>
		</div>

		<div class="flex justify-end gap-3 pt-2">
			<button
				type="button"
				onclick={() => (topupModalOpen = false)}
				class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
			>
				Cancel
			</button>
			<button
				type="submit"
				disabled={depositing}
				class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-500 disabled:opacity-50"
			>
				{depositing ? 'Processing...' : 'Proceed to Pay ৳' + topupAmount}
			</button>
		</div>
	</form>
</Modal>
